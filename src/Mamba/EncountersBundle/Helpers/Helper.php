<?php
namespace Mamba\EncountersBundle\Helpers;

use Core\RedisBundle\Redis;
use Core\MemcacheBundle\Memcache;
use Core\GearmanBundle\Gearman;
use Core\LeveldbBundle\Leveldb;
use Core\MySQLBundle\MySQL;
use Core\MambaBundle\API\Mamba;

use Doctrine\ORM\EntityManager;

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
     * Leveldb getter
     *
     * @return Leveldb
     */
    public function getLeveldb() {
        return $this->Container->get('leveldb');
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
     * MySQL getter
     *
     * @return MySQL
     */
    public function getMySQL() {
        return $this->Container->get('mysql');
    }

    /**
     * Entity Manager getter
     *
     * @return EntityManager
     */
    public function getEntityManager() {
        return $this->getDoctrine()->getEntityManager();
    }

    /**
     * Mamba getter
     *
     * @return Mamba
     */
    public function getMamba() {
        return $this->Container->get('mamba');
    }
}