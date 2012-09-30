<?php
namespace Mamba\EncountersBundle\Helpers;

use Core\RedisBundle\Redis;
use Core\MemcacheBundle\Memcache;

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
        $Container = null
    ;

    /**
     * Конструктор
     *
     * @return null
     */
    public function __construct($Container) {
        $this->Container = $Container;
    }

    /**
     * Redis getter
     *
     * @return Redis
     */
    public function getRedis() {
        return $this->Container->get('redis');
    }

    /**
     * Gearman getter
     *
     * @return Gearman
     */
    public function getGearman() {
        return $this->Container->get('gearman');
    }

    /**
     * Memcache getter
     *
     * @return Memcache
     */
    public function getMemcache() {
        return $this->Container->get('memcache');
    }

    /**
     * Doctrine getter
     *
     * @return Doctrine
     */
    public function getDoctrine() {
        return $this->Container->get('doctrine');
    }

    /**
     * Entity Manager getter
     *
     * @return EntityManager
     */
    public function getEntityManager() {
        return $this->getDoctrine()->getEntityManager();
    }
}