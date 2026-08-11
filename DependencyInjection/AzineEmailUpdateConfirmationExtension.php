<?php

declare(strict_types=1);

namespace Azine\EmailUpdateConfirmationBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class AzineEmailUpdateConfirmationExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
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

        $container->setParameter('azine_email_update_confirmation.template', $config['email_template']);
        $container->setParameter('azine_email_update_confirmation.cypher_method', $config['cypher_method']);
        $container->setParameter('azine_email_update_confirmation.redirect_route', $config['redirect_route']);
        $container->setParameter('azine_email_update_confirmation.from_email', $fromEmail);

        (new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config')))->load('services.yml');

        $container
            ->setAlias('email_update.mailer', $config['mailer'])
            ->setPublic(false);
    }
}
