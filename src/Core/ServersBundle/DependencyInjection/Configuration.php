<?php

namespace Core\ServersBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface {
    /**
     * Config builder
     *
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder() {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('servers');

        $rootNode->children()
            ->arrayNode('www')
                ->isRequired()
                ->requiresAtLeastOneElement()
                ->prototype('variable')
//                    ->children()
//                        ->scalarNode('label')
//                            ->isRequired()
//                            ->cannotBeEmpty()
//                            ->end()
//                        ->scalarNode('host')
//                            ->isRequired()
//                            ->cannotBeEmpty()
//                            ->end()
//                    ->end()
                ->end()
            ->end()
            ->arrayNode('memory')
                ->isRequired()
                ->requiresAtLeastOneElement()
                ->prototype('variable')
//                    ->children()
//                        ->scalarNode('label')
//                            ->isRequired()
//                            ->cannotBeEmpty()
//                            ->end()
//                        ->scalarNode('host')
//                            ->isRequired()
//                            ->cannotBeEmpty()
//                            ->end()
//                    ->end()
                ->end()
            ->end()
            ->arrayNode('storage')
                ->isRequired()
                ->requiresAtLeastOneElement()
                ->prototype('variable')
//                    ->children()
//                        ->scalarNode('label')
//                            ->isRequired()
//                            ->cannotBeEmpty()
//                            ->end()
//                        ->scalarNode('host')
//                            ->isRequired()
//                            ->cannotBeEmpty()
//                            ->end()
//                    ->end()
                ->end()
            ->end()
            ->arrayNode('script')
                ->isRequired()
                ->requiresAtLeastOneElement()
                ->prototype('variable')
//                    ->children()
//                        ->scalarNode('label')
//                            ->isRequired()
//                            ->cannotBeEmpty()
//                            ->end()
//                        ->scalarNode('host')
//                            ->isRequired()
//                            ->cannotBeEmpty()
//                            ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
