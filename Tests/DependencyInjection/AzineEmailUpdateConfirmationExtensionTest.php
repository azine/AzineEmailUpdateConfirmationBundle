<?php

declare(strict_types=1);

namespace Azine\EmailUpdateConfirmationBundle\Tests\DependencyInjection;

use Azine\EmailUpdateConfirmationBundle\DependencyInjection\AzineEmailUpdateConfirmationExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AzineEmailUpdateConfirmationExtensionTest extends TestCase
{
    public function testCanDisableEmailUpdateConfirmation(): void
    {
        $container = new ContainerBuilder();
        (new AzineEmailUpdateConfirmationExtension())->load([
            ['enabled' => false, 'from_email' => 'test@example.com'],
        ], $container);

        self::assertFalse($container->hasDefinition('email_update_confirmation'));
        self::assertFalse($container->hasAlias('email_update.mailer'));
        self::assertFalse($container->hasDefinition('email_update_listener'));
        self::assertFalse($container->hasDefinition('email_update_flash_subscriber'));
        self::assertFalse($container->hasParameter('azine_email_update_confirmation.template'));
    }

    public function testEnablesEmailUpdateConfirmationWithExplicitSender(): void
    {
        $container = new ContainerBuilder();
        (new AzineEmailUpdateConfirmationExtension())->load([
            ['from_email' => 'test@example.com'],
        ], $container);

        self::assertTrue($container->hasDefinition('email_update_confirmation'));
        self::assertTrue($container->hasDefinition('azine.email_update.default_mailer'));
        self::assertTrue($container->hasAlias('email_update.mailer'));
        self::assertTrue($container->hasDefinition('email_update_listener'));
        self::assertTrue($container->hasDefinition('email_update_flash_subscriber'));
        self::assertSame(
            'test@example.com',
            $container->getParameter('azine_email_update_confirmation.from_email'),
        );
    }

    public function testRequiresSenderWhenFosUserFallbackIsUnavailable(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new AzineEmailUpdateConfirmationExtension())->load([], new ContainerBuilder());
    }

    public function testUsesCompleteFosUserFromAddressConfiguration(): void
    {
        $container = new ContainerBuilder();
        $fromEmail = [
            'address' => 'fosuserbundle.from.email@example.com',
            'sender_name' => 'From Email Name',
        ];
        $container->setParameter('fos_user.resetting.email.from_email', $fromEmail);

        (new AzineEmailUpdateConfirmationExtension())->load([], $container);

        self::assertSame(
            $fromEmail,
            $container->getParameter('azine_email_update_confirmation.from_email'),
        );
    }
}
