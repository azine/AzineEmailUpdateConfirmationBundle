<?php

declare(strict_types=1);

namespace Azine\EmailUpdateConfirmationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('azine_email_update_confirmation');

        $treeBuilder->getRootNode()
            ->children()
                ->booleanNode('enabled')
                    ->defaultTrue()
                ->end()
                ->scalarNode('cipher_method')
                    ->cannotBeEmpty()
                    ->defaultValue('AES-128-CBC')
                ->end()
                ->scalarNode('cypher_method')
                    ->defaultNull()
                    ->info('Deprecated misspelling retained for migration; use cipher_method.')
                ->end()
                ->integerNode('confirmation_ttl')
                    ->min(60)
                    ->defaultValue(86400)
                ->end()
                ->booleanNode('allow_legacy_payloads')
                    ->defaultTrue()
                ->end()
                ->scalarNode('mailer')
                    ->cannotBeEmpty()
                    ->defaultValue('azine.email_update.default_mailer')
                ->end()
                ->scalarNode('email_template')
                    ->cannotBeEmpty()
                    ->defaultValue('@AzineEmailUpdateConfirmation/Email/email_update_confirmation.txt.twig')
                ->end()
                ->variableNode('from_email')
                    ->defaultNull()
                    ->validate()
                        ->ifTrue(static fn (mixed $value): bool => null !== $value && !is_string($value) && !is_array($value))
                        ->thenInvalid('Expected from_email to be a string or an address/name array.')
                    ->end()
                ->end()
                ->scalarNode('redirect_route')
                    ->cannotBeEmpty()
                    ->defaultValue('fos_user_profile_show')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
