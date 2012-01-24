<?php
namespace Mamba\RedisBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration
 *
 * @package RedisBundle
 */
class Configuration implements ConfigurationInterface {

    /**
     * Config builder
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder
     */
    public function getConfigTreeBuilder() {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('redis');

        $rootNode
            ->children()
                ->scalarNode('host')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->end()
                ->scalarNode('port')
                    ->defaultValue(6379)
                    ->cannotBeEmpty()
                    ->end()
                ->scalarNode('timeout')
                    ->defaultValue(0)
                    ->end()
                ->scalarNode('database')
                    ->isRequired()
                    ->defaultValue(0)
                    ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
