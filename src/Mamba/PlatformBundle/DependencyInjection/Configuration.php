<?php
namespace Mamba\PlatformBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration
 *
 * @package PlatformBundle
 */
class Configuration implements ConfigurationInterface {

    /**
     * Config builder
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder
     */
    public function getConfigTreeBuilder() {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('mamba');

        $rootNode
            ->children()
                ->scalarNode('app_id')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->end()
                ->scalarNode('secret_key')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->end()
                ->scalarNode('private_key')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
