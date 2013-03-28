<?php

namespace Core\LeveldbBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('leveldb');

        $rootNode->children()

            ->arrayNode('master')
                ->isRequired()
                ->requiresAtLeastOneElement()
                ->prototype('array')
                    ->children()
                        ->scalarNode('host')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->end()
                        ->scalarNode('port')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->end()
                        ->scalarNode('timeout')
                            ->isRequired()
                            ->end()
                    ->end()
                ->end()
            ->end()

            ->arrayNode('slave')
                ->isRequired()
                ->requiresAtLeastOneElement()
                ->prototype('array')
                    ->children()
                        ->scalarNode('host')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->end()
                        ->scalarNode('port')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->end()
                        ->scalarNode('timeout')
                            ->isRequired()
                            ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
