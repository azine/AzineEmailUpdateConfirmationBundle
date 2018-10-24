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
                ->booleanNode('enabled')->defaultTrue()->info('enable/disable email update confirmation functionality. default = true')->end()
                ->scalarNode('cypher_method')->defaultNull()->info('determines the encryption mode for encryption of email value. openssl_get_cipher_methods(false) is default value')->end()
                ->scalarNode('mailer')->defaultValue('azine.email_update.mailer')->info('mailer service to be used')->end()
                ->scalarNode('email_template')->defaultValue('@AzineEmailUpdateConfirmation/Email/email_update_confirmation.txt.twig')->info('email template')->end()
                ->scalarNode('from_email')->info('`from`-address for the email. If not set, `fos_user.resetting.email.from_email` will be used')->end()
                ->scalarNode('redirect_route')->defaultValue('fos_user_profile_show')->info('route to redirect to, after the update confirmation')->end()
            ->end();

        return $treeBuilder;
    }
}
