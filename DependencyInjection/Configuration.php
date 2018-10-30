<?php

namespace Azine\EmailUpdateConfirmationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('azine_email_update_confirmation');

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        $rootNode->children()
                ->booleanNode('enabled')->defaultTrue()->info('enables email update confirmation functionality')->end()
                ->scalarNode('cypher_method')->defaultNull()->info('determines the encryption mode for encryption of email value. openssl_get_cipher_methods(false) is default value')->end()
                ->scalarNode('mailer')->defaultValue('azine.email_update.mailer')->info('mailer service')->end()
                ->scalarNode('email_template')->defaultValue('@AzineEmailUpdateConfirmation/Email/email_update_confirmation.txt.twig')->info('email template file')->end()
                ->scalarNode('from_email')->defaultNull()->info('from email')->end()
                ->scalarNode('redirect_route')->defaultValue('fos_user_profile_show')->info('from email')->end()
            ->end();

        return $treeBuilder;
    }
}
