<?php

declare(strict_types=1);

namespace Azine\EmailUpdateConfirmationBundle\Tests\Controller;

use Azine\EmailUpdateConfirmationBundle\AzineEmailUpdateConfirmationEvents;
use Azine\EmailUpdateConfirmationBundle\Controller\ConfirmEmailUpdateController;
use Azine\EmailUpdateConfirmationBundle\Mailer\EmailUpdateConfirmationMailerInterface;
use Azine\EmailUpdateConfirmationBundle\Services\EmailUpdateConfirmation;
use FOS\UserBundle\Event\UserEvent;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Util\CanonicalFieldsUpdater;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AllowMockObjectsWithoutExpectations]
final class ConfirmEmailUpdateControllerTest extends TestCase
{
    private const TOKEN = 'confirmation-token';

    public function testValidConfirmationUpdatesEmailAndIgnoresLegacyRedirectRoute(): void
    {
        $email = 'old@example.test';
        $canonicalEmail = 'old@example.test';
        $confirmationToken = self::TOKEN;
        $user = $this->statefulUser(42, $email, $canonicalEmail, $confirmationToken);

        $userManager = $this->createMock(UserManagerInterface::class);
        $userManager
            ->expects(self::once())
            ->method('findUserByConfirmationToken')
            ->with(self::TOKEN)
            ->willReturn($user);
        $userManager
            ->expects(self::once())
            ->method('updateUser')
            ->with($user);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(
                self::isInstanceOf(UserEvent::class),
                AzineEmailUpdateConfirmationEvents::EMAIL_UPDATE_SUCCESS,
            )
            ->willReturnArgument(0);

        $canonicalFieldsUpdater = $this->createMock(CanonicalFieldsUpdater::class);
        $canonicalFieldsUpdater
            ->expects(self::once())
            ->method('canonicalizeEmail')
            ->with('New@Example.test')
            ->willReturn('new@example.test');

        $controller = new ConfirmEmailUpdateController(
            $dispatcher,
            $userManager,
            $this->confirmationService(),
            $this->translator(),
            $canonicalFieldsUpdater,
            'safe_profile_route',
        );

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects(self::once())
            ->method('generate')
            ->with('safe_profile_route', [], 1)
            ->willReturn('/profile');

        $controller->setContainer($this->controllerContainer($user, $router));

        $target = $this->confirmationService()->encryptEmailValue(self::TOKEN, 'New@Example.test');
        $response = $controller->confirmEmailUpdateAction(
            Request::create('/confirm?target='.rawurlencode($target), 'GET', ['target' => $target]),
            self::TOKEN,
            'attacker_controlled_route',
        );

        self::assertSame('/profile', $response->getTargetUrl());
        self::assertSame('New@Example.test', $email);
        self::assertSame('new@example.test', $canonicalEmail);
        self::assertSame(base64_encode(EmailUpdateConfirmation::EMAIL_CONFIRMED), $confirmationToken);
    }

    public function testDifferentAuthenticatedUserIsRejected(): void
    {
        $email = 'old@example.test';
        $canonicalEmail = 'old@example.test';
        $confirmationToken = self::TOKEN;
        $tokenOwner = $this->statefulUser(42, $email, $canonicalEmail, $confirmationToken);

        $otherEmail = 'other@example.test';
        $otherCanonical = 'other@example.test';
        $otherToken = null;
        $authenticatedUser = $this->statefulUser(99, $otherEmail, $otherCanonical, $otherToken);

        $userManager = $this->createStub(UserManagerInterface::class);
        $userManager->method('findUserByConfirmationToken')->willReturn($tokenOwner);

        $controller = new ConfirmEmailUpdateController(
            $this->createStub(EventDispatcherInterface::class),
            $userManager,
            $this->confirmationService(),
            $this->translator(),
            $this->createStub(CanonicalFieldsUpdater::class),
            'safe_profile_route',
        );
        $controller->setContainer($this->controllerContainer($authenticatedUser, $this->createStub(RouterInterface::class)));

        $this->expectException(AccessDeniedException::class);
        $controller->confirmEmailUpdateAction(new Request(['target' => 'irrelevant']), self::TOKEN);
    }

    private function confirmationService(): EmailUpdateConfirmation
    {
        return new EmailUpdateConfirmation(
            $this->createStub(RouterInterface::class),
            $this->createStub(TokenGeneratorInterface::class),
            $this->createStub(EmailUpdateConfirmationMailerInterface::class),
            $this->createStub(EventDispatcherInterface::class),
            Validation::createValidator(),
            'safe_profile_route',
            confirmationTtl: 3600,
            nowProvider: static fn (): int => 1_000,
        );
    }

    private function translator(): TranslatorInterface
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturn('The confirmation link is not valid.');

        return $translator;
    }

    private function controllerContainer(UserInterface $authenticatedUser, RouterInterface $router): Container
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($authenticatedUser);

        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $container = new Container();
        $container->set('security.token_storage', $tokenStorage);
        $container->set('router', $router);

        return $container;
    }

    private function statefulUser(
        int $id,
        string &$email,
        string &$canonicalEmail,
        ?string &$confirmationToken,
    ): UserInterface {
        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn($id);
        $user->method('getEmail')->willReturnCallback(static function () use (&$email): string {
            return $email;
        });
        $user->method('setEmail')->willReturnCallback(static function (?string $value) use (&$email): void {
            $email = (string) $value;
        });
        $user->method('getEmailCanonical')->willReturnCallback(static function () use (&$canonicalEmail): string {
            return $canonicalEmail;
        });
        $user->method('setEmailCanonical')->willReturnCallback(static function (?string $value) use (&$canonicalEmail): void {
            $canonicalEmail = (string) $value;
        });
        $user->method('getConfirmationToken')->willReturnCallback(static function () use (&$confirmationToken): ?string {
            return $confirmationToken;
        });
        $user->method('setConfirmationToken')->willReturnCallback(static function (?string $value) use (&$confirmationToken): void {
            $confirmationToken = $value;
        });

        return $user;
    }
}
