<?php
namespace Core\RedisBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * RedisExtension
 *
 * @package RedisBundle
 */
class RedisExtension extends Extension {

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container) {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter("redis.nodes", $config['nodes']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
