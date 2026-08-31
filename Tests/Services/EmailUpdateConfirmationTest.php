<?php

declare(strict_types=1);

namespace Azine\EmailUpdateConfirmationBundle\Tests\Services;

use Azine\EmailUpdateConfirmationBundle\AzineEmailUpdateConfirmationEvents;
use Azine\EmailUpdateConfirmationBundle\Mailer\EmailUpdateConfirmationMailerInterface;
use Azine\EmailUpdateConfirmationBundle\Services\EmailUpdateConfirmation;
use FOS\UserBundle\Event\UserEvent;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AllowMockObjectsWithoutExpectations]
final class EmailUpdateConfirmationTest extends TestCase
{
    private const TOKEN = 'confirmation-token';

    public function testAuthenticatedPayloadRoundTrips(): void
    {
        $service = $this->createService(now: 1_000);

        $payload = $service->encryptEmailValue(self::TOKEN, 'new@example.test');

        self::assertStringStartsWith('v2.', $payload);
        self::assertSame(
            'new@example.test',
            $service->decryptEmailValue(self::TOKEN, $payload),
        );
    }

    public function testTamperedPayloadIsRejected(): void
    {
        $service = $this->createService(now: 1_000);
        $payload = $service->encryptEmailValue(self::TOKEN, 'new@example.test');
        $last = substr($payload, -1);
        $tampered = substr($payload, 0, -1).('A' === $last ? 'B' : 'A');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('modified');

        $service->decryptEmailValue(self::TOKEN, $tampered);
    }

    public function testExpiredPayloadIsRejected(): void
    {
        $payload = $this->createService(now: 1_000, ttl: 60)
            ->encryptEmailValue(self::TOKEN, 'new@example.test');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('expired');

        $this->createService(now: 1_061, ttl: 60)
            ->decryptEmailValue(self::TOKEN, $payload);
    }

    public function testLegacyPayloadRemainsReadableDuringMigration(): void
    {
        $legacyPayload = $this->createLegacyPayload(self::TOKEN, 'legacy@example.test');

        self::assertSame(
            'legacy@example.test',
            $this->createService(now: 1_000)->decryptEmailValue(self::TOKEN, $legacyPayload),
        );
    }

    public function testLegacyPayloadCanBeDisabled(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('disabled');

        $this->createService(now: 1_000, allowLegacy: false)
            ->decryptEmailValue(
                self::TOKEN,
                $this->createLegacyPayload(self::TOKEN, 'legacy@example.test'),
            );
    }

    public function testInvalidEmailIsRejectedBeforeEncryption(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('valid email');

        $this->createService(now: 1_000)
            ->encryptEmailValue(self::TOKEN, 'not-an-email');
    }

    public function testEmptyTokenIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must not be empty');

        $this->createService(now: 1_000)
            ->encryptEmailValue('', 'new@example.test');
    }

    public function testGeneratedLinksDoNotExposeAUserControlledRedirectRoute(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects(self::once())
            ->method('generate')
            ->with(
                'user_update_email_confirm',
                self::callback(static function (array $parameters): bool {
                    return 'generated-token' === $parameters['token']
                        && str_starts_with((string) $parameters['target'], 'v2.')
                        && !array_key_exists('redirectRoute', $parameters);
                }),
                UrlGeneratorInterface::ABSOLUTE_URL,
            )
            ->willReturn('https://example.test/confirm-email-update/generated-token');

        $tokenGenerator = $this->createMock(TokenGeneratorInterface::class);
        $tokenGenerator->method('generateToken')->willReturn('generated-token');

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(
                self::isInstanceOf(UserEvent::class),
                AzineEmailUpdateConfirmationEvents::EMAIL_UPDATE_INITIALIZE,
            )
            ->willReturnArgument(0);

        $token = null;
        $user = $this->createMock(UserInterface::class);
        $user->method('getConfirmationToken')->willReturnCallback(static function () use (&$token): ?string {
            return $token;
        });
        $user->method('setConfirmationToken')->willReturnCallback(static function (?string $newToken) use (&$token): void {
            $token = $newToken;
        });

        $service = new EmailUpdateConfirmation(
            $router,
            $tokenGenerator,
            $this->createStub(EmailUpdateConfirmationMailerInterface::class),
            $dispatcher,
            Validation::createValidator(),
            'safe_route',
            confirmationTtl: 60,
            nowProvider: static fn (): int => 1_000,
        );

        self::assertSame(
            'https://example.test/confirm-email-update/generated-token',
            $service->generateConfirmationLink(new Request(), $user, 'new@example.test'),
        );
        self::assertSame('generated-token', $token);
    }

    private function createService(
        int $now,
        int $ttl = 60,
        bool $allowLegacy = true,
        ?RouterInterface $router = null,
        ?TokenGeneratorInterface $tokenGenerator = null,
        ?EventDispatcherInterface $dispatcher = null,
        ?ValidatorInterface $validator = null,
    ): EmailUpdateConfirmation {
        return new EmailUpdateConfirmation(
            $router ?? $this->createStub(RouterInterface::class),
            $tokenGenerator ?? $this->createStub(TokenGeneratorInterface::class),
            $this->createStub(EmailUpdateConfirmationMailerInterface::class),
            $dispatcher ?? $this->createStub(EventDispatcherInterface::class),
            $validator ?? Validation::createValidator(),
            'safe_route',
            confirmationTtl: $ttl,
            allowLegacyPayloads: $allowLegacy,
            nowProvider: static fn (): int => $now,
        );
    }

    private function createLegacyPayload(string $token, string $email): string
    {
        $cipher = 'AES-128-CBC';
        $ivSize = openssl_cipher_iv_length($cipher);
        self::assertIsInt($ivSize);
        $iv = random_bytes($ivSize);
        $encrypted = openssl_encrypt(
            $email,
            $cipher,
            pack('H*', hash('sha256', $token)),
            0,
            $iv,
        );
        self::assertIsString($encrypted);

        return base64_encode($iv.$encrypted);
    }
}
