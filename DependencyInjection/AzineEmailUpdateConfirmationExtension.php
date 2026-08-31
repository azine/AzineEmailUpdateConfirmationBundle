<?php

declare(strict_types=1);

namespace Azine\EmailUpdateConfirmationBundle\DependencyInjection;

use Azine\EmailUpdateConfirmationBundle\Mailer\AzineEmailBundleMailerAdapter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class AzineEmailUpdateConfirmationExtension extends Extension
{
    private const EMAIL_BUNDLE_MAILERS = [
        'azine_email.default.template_twig_mailer',
        'azine_email.default.template_twig_swift_mailer',
    ];

    /**
     * @param array<array-key, mixed> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var array{
         *     enabled: bool,
         *     cipher_method: string,
         *     cypher_method: ?string,
         *     confirmation_ttl: int,
         *     allow_legacy_payloads: bool,
         *     mailer: string,
         *     email_template: string,
         *     from_email: mixed,
         *     redirect_route: string
         * } $config
         */
        $config = $this->processConfiguration(new Configuration(), $configs);
        if (!$config['enabled']) {
            return;
        }

        $fromEmail = $config['from_email'];
        if (null === $fromEmail || '' === $fromEmail || [] === $fromEmail) {
            if (!$container->hasParameter('fos_user.resetting.email.from_email')) {
                throw new \InvalidArgumentException(
                    'Configure azine_email_update_confirmation.from_email or enable the FOSUser resetting email configuration.',
                );
            }

            $fromEmail = $container->getParameter('fos_user.resetting.email.from_email');
        }

        $cipherMethod = $config['cypher_method'] ?: $config['cipher_method'];

        $container->setParameter('azine_email_update_confirmation.template', $config['email_template']);
        $container->setParameter('azine_email_update_confirmation.cipher_method', $cipherMethod);
        $container->setParameter('azine_email_update_confirmation.confirmation_ttl', $config['confirmation_ttl']);
        $container->setParameter('azine_email_update_confirmation.allow_legacy_payloads', $config['allow_legacy_payloads']);
        $container->setParameter('azine_email_update_confirmation.redirect_route', $config['redirect_route']);
        $container->setParameter('azine_email_update_confirmation.from_email', $fromEmail);

        (new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config')))->load('services.yml');

        if (in_array($config['mailer'], self::EMAIL_BUNDLE_MAILERS, true)) {
            $container->setDefinition(
                'azine.email_update.email_bundle_mailer',
                (new Definition(AzineEmailBundleMailerAdapter::class, [
                    new Reference($config['mailer']),
                ]))->setPublic(false),
            );
            $mailerService = 'azine.email_update.email_bundle_mailer';
        } else {
            $mailerService = $config['mailer'];
        }

        $container
            ->setAlias('email_update.mailer', $mailerService)
            ->setPublic(false);
    }
}
