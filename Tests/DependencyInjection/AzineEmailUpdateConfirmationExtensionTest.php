<?php

declare(strict_types=1);

namespace Azine\EmailUpdateConfirmationBundle\Tests\DependencyInjection;

use Azine\EmailUpdateConfirmationBundle\DependencyInjection\AzineEmailUpdateConfirmationExtension;
use Azine\EmailUpdateConfirmationBundle\Mailer\AzineEmailBundleMailerAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class AzineEmailUpdateConfirmationExtensionTest extends TestCase
{
    public function testLoadsSecureDefaults(): void
    {
        $container = $this->container();
        (new AzineEmailUpdateConfirmationExtension())->load([], $container);

        self::assertSame('AES-128-CBC', $container->getParameter('azine_email_update_confirmation.cipher_method'));
        self::assertSame(86400, $container->getParameter('azine_email_update_confirmation.confirmation_ttl'));
        self::assertTrue($container->getParameter('azine_email_update_confirmation.allow_legacy_payloads'));
        self::assertSame('fos_user_profile_show', $container->getParameter('azine_email_update_confirmation.redirect_route'));
        self::assertSame('azine.email_update.default_mailer', (string) $container->getAlias('email_update.mailer'));

        $listener = $container->getDefinition('email_update_listener');
        $events = array_column($listener->getTags()['doctrine.event_listener'], 'event');
        self::assertSame(['onFlush', 'postFlush'], $events);
    }

    public function testWrapsTheStableAzineEmailBundleMailer(): void
    {
        $container = $this->container();
        $container->setDefinition('azine_email.default.template_twig_mailer', new Definition(\stdClass::class));

        (new AzineEmailUpdateConfirmationExtension())->load([
            [
                'mailer' => 'azine_email.default.template_twig_mailer',
                'confirmation_ttl' => 3600,
            ],
        ], $container);

        self::assertSame(
            'azine.email_update.email_bundle_mailer',
            (string) $container->getAlias('email_update.mailer'),
        );
        self::assertSame(
            AzineEmailBundleMailerAdapter::class,
            $container->getDefinition('azine.email_update.email_bundle_mailer')->getClass(),
        );
        self::assertSame(3600, $container->getParameter('azine_email_update_confirmation.confirmation_ttl'));
    }

    public function testDeprecatedCypherSettingStillOverridesTheCorrectSpelling(): void
    {
        $container = $this->container();
        (new AzineEmailUpdateConfirmationExtension())->load([
            [
                'cipher_method' => 'AES-256-CBC',
                'cypher_method' => 'AES-128-CBC',
            ],
        ], $container);

        self::assertSame('AES-128-CBC', $container->getParameter('azine_email_update_confirmation.cipher_method'));
    }

    public function testDisabledBundleLoadsNoServices(): void
    {
        $container = $this->container();
        (new AzineEmailUpdateConfirmationExtension())->load([
            ['enabled' => false],
        ], $container);

        self::assertFalse($container->hasDefinition('email_update_confirmation'));
    }

    private function container(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('fos_user.resetting.email.from_email', [
            'address' => 'no-reply@example.test',
            'sender_name' => 'Example',
        ]);

        return $container;
    }
}
