<?php
namespace Mamba\EncountersBundle;

use Mamba\RedisBundle\Redis;
use Mamba\MemcacheBundle\Memcache;

/**
 * Energy
 *
 * @package EncountersBundle
 */
class Energy {

    const

        /**
         * Энергия по умолчанию
         *
         * @var int
         */
        DEFAULT_ENERGY = 512,

        /**
         * Минимальная энергия
         *
         * @var int
         */
        MINIMUM_ENERGY = 128,

        /**
         * Максимальная энергия
         *
         * @var int
         */
        MAXIMUM_ENERGY = 2048,

        /**
         * Ключ для хранения энергии
         *
         * @var str
         */
        REDIS_HASH_USERS_ENERGIES_KEY = "energies"
    ;

    private

        /**
         * Redis
         *
         * @var Redis
         */
        $Redis = null
    ;

    /**
     * Конструктор
     *
     * @param \Mamba\RedisBundle\Redis $Redis
     */
    public function __construct(Redis $Redis) {
        $this->Redis = $Redis;
    }

    /**
     * Energy getter
     *
     * @param int $userId
     * @return mixed
     */
    public function get($userId) {
        if (!is_int($userId)) {
            throw new EnergyException("Invalid user id: \n" . var_export($userId, true));
        }

        $energy = $this->Redis->hGet(self::REDIS_HASH_USERS_ENERGIES_KEY, $userId);
        if (false === $energy) {
            $this->set($userId, $energy = self::DEFAULT_ENERGY);
        }

        return $energy;
    }

    /**
     * Energy setter
     *
     * @param int $userId
     * @param int $energy
     * @return mixed
     */
    public function set($userId, $energy) {
        if (!is_int($userId)) {
            throw new EnergyException("Invalid user id: \n" . var_export($userId, true));
        }

        if (is_int($energy) && $energy >= self::MINIMUM_ENERGY && $energy <= self::MAXIMUM_ENERGY) {
            return $this->Redis->hSet(self::REDIS_HASH_USERS_ENERGIES_KEY, $userId, $energy);
        }

        throw new EnergyException("Invalid energy: \n" . var_export($energy, true));
    }

    /**
     * Atomic increment
     *
     * @param int $userId
     * @param int $rate
     */
    public function incr($userId, $rate = 1) {
        if (!is_int($userId)) {
            throw new EnergyException("Invalid user id: \n" . var_export($userId, true));
        }

        if (!is_int($rate)) {
            throw new EnergyException("Invalid increment rate: \n" . var_export($rate, true));
        }

        return $this->Redis->hIncrBy(self::REDIS_HASH_USERS_ENERGIES_KEY, $userId, $rate);
    }

    /**
     * Atomic decrement
     *
     * @param int $userId
     * @param int $rate
     */
    public function decr($userId, $rate = 1) {
        if (!is_int($rate)) {
            throw new EnergyException("Invalid decrement rate: \n" . var_export($rate, true));
        }

        return $this->incr($userId, $rate * -1);
    }
}

/**
 * EnergyException
 *
 * @package EncountersBundle
 */
class EnergyException extends \Exception {

}