<?php
namespace Core\RedisBundle\DependencyInjection;

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

        $rootNode->children()
            ->arrayNode('nodes')
                ->isRequired()
                ->requiresAtLeastOneElement()
                ->prototype('variable')
                ->end()
            ->end()

        ->end();

        return $treeBuilder;
    }
}
