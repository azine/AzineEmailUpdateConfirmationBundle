<?php

namespace Azine\EmailUpdateConfirmationBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @see http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class AzineEmailUpdateConfirmationExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        if ($config['enabled']) {
            $loader->load('services.yml');
            $container->setParameter('azine_email_update_confirmation.template', $config['email_template']);
            $container->setParameter('azine_email_update_confirmation.cypher_method', $config['cypher_method']);
            $container->setParameter('azine_email_update_confirmation.redirect_route', $config['redirect_route']);
            $container->setParameter('azine_email_update_confirmation.from_email', $config['from_email']);

            if($config['from_email'] == null){

                try{

                    $fromEmail = array_keys($container->getParameter('fos_user.resetting.email.from_email'))[0];
                    $container->setParameter('azine_email_update_confirmation.from_email', $fromEmail);
                }
                catch (\Exception $e){

                    throw new \Exception('Set up from_email parameter under azine_email_update_confirmation');
                }
            }

            $container->setAlias('email_update.mailer', $config['mailer']);
        }
    }
}
