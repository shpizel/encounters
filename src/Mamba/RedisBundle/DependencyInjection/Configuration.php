<?php

namespace Mamba\RedisBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('redis');

        $rootNode
            ->children()
                ->scalarNode('host')
                    ->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('port')
                    ->defaultValue(6379)->cannotBeEmpty()->end()
                ->scalarNode('timeout')
                    ->defaultValue(0)->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
