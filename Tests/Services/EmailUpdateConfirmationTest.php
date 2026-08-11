<?php

declare(strict_types=1);

namespace Azine\EmailUpdateConfirmationBundle\Tests\Services;

use Azine\EmailUpdateConfirmationBundle\Mailer\EmailUpdateConfirmationMailerInterface;
use Azine\EmailUpdateConfirmationBundle\Services\EmailUpdateConfirmation;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EmailUpdateConfirmationTest extends TestCase
{
    private const TOKEN = 'test-token';
    private const EMAIL = 'foo@example.com';

    public function testEncryptsAndDecryptsEmail(): void
    {
        $service = $this->createService(new ConstraintViolationList());
        $encryptedEmail = $service->encryptEmailValue(self::TOKEN, self::EMAIL);

        self::assertNotSame(self::EMAIL, $encryptedEmail);
        self::assertSame(self::EMAIL, $service->decryptEmailValue(self::TOKEN, $encryptedEmail));
    }

    public function testFetchesEmailFromConfirmationPayload(): void
    {
        $service = $this->createService(new ConstraintViolationList());
        $user = $this->createMock(UserInterface::class);
        $user->method('getConfirmationToken')->willReturn(self::TOKEN);

        $encryptedEmail = $service->encryptEmailValue(self::TOKEN, self::EMAIL);

        self::assertSame(
            self::EMAIL,
            $service->fetchEncryptedEmailFromConfirmationLink($user, $encryptedEmail),
        );
    }

    public function testRejectsDecryptedInvalidEmail(): void
    {
        $violations = new ConstraintViolationList([
            $this->createMock(ConstraintViolationInterface::class),
        ]);
        $service = $this->createService($violations);
        $encryptedEmail = $service->encryptEmailValue(self::TOKEN, 'not-an-email');

        $this->expectException(\InvalidArgumentException::class);
        $service->decryptEmailValue(self::TOKEN, $encryptedEmail);
    }

    public function testRejectsMalformedPayload(): void
    {
        $service = $this->createService(new ConstraintViolationList());

        $this->expectException(\InvalidArgumentException::class);
        $service->decryptEmailValue(self::TOKEN, 'not-valid-base64***');
    }

    public function testRejectsEmptyToken(): void
    {
        $service = $this->createService(new ConstraintViolationList());

        $this->expectException(\InvalidArgumentException::class);
        $service->encryptEmailValue('', self::EMAIL);
    }

    private function createService(ConstraintViolationList $violations): EmailUpdateConfirmation
    {
        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('validate')->willReturn($violations);

        return new EmailUpdateConfirmation(
            $this->createMock(RouterInterface::class),
            $this->createMock(TokenGeneratorInterface::class),
            $this->createMock(EmailUpdateConfirmationMailerInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $validator,
            'redirect_route',
            'AES-128-CBC',
        );
    }
}
