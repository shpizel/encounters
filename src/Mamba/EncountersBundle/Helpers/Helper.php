<?php
namespace Mamba\EncountersBundle\Helpers;

/**
 * Helper
 *
 * @package EncountersBundle
 */
abstract class Helper {

    protected

        /**
         * Container
         *
         * @var object
         */
        $container = null
    ;

    /**
     * Конструктор
     *
     * @return null
     */
    public function __construct($container) {
        $this->container = $container;
    }

    /**
     * Redis getter
     *
     * @return Redis
     */
    public function getRedis() {
        return $this->container->get('redis');
    }

    /**
     * Memcache getter
     *
     * @return Memcache
     */
    public function getMemcache() {
        return $this->container->get('memcache');
    }

    /**
     * Mamba getter
     *
     * @return Mamba
     */
    public function getMamba() {
        return $this->container->get('mamba');
    }

    /**
     * Gearman getter
     *
     * @return Gearman
     */
    public function getGearman() {
        return $this->container->get('gearman');
    }
}