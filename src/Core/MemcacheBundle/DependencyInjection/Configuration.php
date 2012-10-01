<?php
namespace Core\MemcacheBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration
 *
 * @package MemcacheBundle
 */
class Configuration implements ConfigurationInterface {

    /**
     * Config builder
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder
     */
    public function getConfigTreeBuilder() {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('memcache');

        $rootNode->children()
            ->arrayNode('nodes')
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
                        ->scalarNode('weight')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
