<?php

declare(strict_types=1);

namespace Azine\EmailUpdateConfirmationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('azine_email_update_confirmation');

        $treeBuilder->getRootNode()
            ->children()
                ->booleanNode('enabled')
                    ->defaultTrue()
                ->end()
                ->scalarNode('cypher_method')
                    ->defaultNull()
                ->end()
                ->scalarNode('mailer')
                    ->defaultValue('azine.email_update.default_mailer')
                ->end()
                ->scalarNode('email_template')
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
