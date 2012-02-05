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
        DEFAULT_ENERGY = 256,

        /**
         * Минимальная энергия
         *
         * @var int
         */
        MINIMUM_ENERGY = 0,

        /**
         * Максимальная энергия
         *
         * @var int
         */
        MAXIMUM_ENERGY = 1024,

        /**
         * Ключ для хранения хеша энергий
         *
         * @var str
         */
        REDIS_HASH_USERS_ENERGIES_KEY = "users_energies",

        /**
         * Ключ для хранения блокировки изменения заряда батарейки
         *
         * @var string
         */
        MEMCACHE_USER_ENERGY_LOCK_KEY = "user_%d_energy_lock_key",

        /**
         * Время жизнь блокировки изменения заряда батарейки
         *
         * @var int
         */
        MEMCACHE_USER_ENERGY_LOCK_EXPIRE = 30
    ;

    private

        /**
         * Redis
         *
         * @var Redis
         */
        $Redis = null,

        /**
         * Memcache
         *
         * @var Memcache
         */
        $Memcache = null
    ;

    /**
     * Конструктор
     *
     * @param $userId
     * @return null
     */
    public function __construct(Redis $Redis, Memcache $Memcache) {
        list($this->Redis, $this->Memcache) = array($Redis, $Memcache);
    }

    /**
     * Energy getter
     *
     * @param int $userId
     * @return mixed
     */
    public function get($userId) {
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
     * @param int $value
     * @return mixed
     */
    public function set($userId, $value) {
        if (is_int($value) && $value >= self::MINIMUM_ENERGY && $value <= self::MAXIMUM_ENERGY) {
            return $this->Redis->hSet(self::REDIS_HASH_USERS_ENERGIES_KEY, $userId, $value);
        }

        throw new EnergyException("Invalid charge: \n" . var_export($value, true));
    }

    /**
     * Atomic increment
     *
     * @param int $userId
     * @param int $rate
     */
    public function incr($userId, $rate = 1) {
        if (is_int($userId) && is_int($rate) && $rate > 0) {
            if ($this->acquireLock($userId)) {

                $currentEnergy = $this->Redis->hGet(self::REDIS_HASH_USERS_ENERGIES_KEY, $userId);
                $updatedEnergy = $currentEnergy + $rate;

                if ($updatedEnergy >= self::MINIMUM_ENERGY && $updatedEnergy <= self::MAXIMUM_ENERGY) {
                    return $this->set($userId, $updatedEnergy);
                } else {
                    $this->releaseLock($userId);
                    throw new EnergyException("Invalid increment result: \n" . var_export($updatedEnergy, true));
                }

                $this->releaseLock($userId);
            }

            throw new EnergyException("Could not obtain a lock");
        }

        throw new EnergyException("Invalid increment rate: \n" . var_export($rate, true));
    }

    /**
     * Atomic decrement
     *
     * @param int $userId
     * @param int $rate
     */
    public function decr($userId, $rate = 1) {
        if (is_int($userId) && is_int($rate) && $rate > 0) {
            if ($this->acquireLock($userId)) {

                $currentEnergy = $this->Redis->hGet(self::REDIS_HASH_USERS_ENERGIES_KEY, $userId);
                $updatedEnergy = $currentEnergy - $rate;

                if ($updatedEnergy >= self::MINIMUM_ENERGY && $updatedEnergy <= self::MAXIMUM_ENERGY) {
                    return $this->set($userId, $updatedEnergy);
                } else {
                    $this->releaseLock($userId);
                    throw new EnergyException("Invalid decrement result: \n" . var_export($updatedEnergy, true));
                }

                $this->releaseLock($userId);
            }

            throw new EnergyException("Could not obtain a lock");
        }

        throw new EnergyException("Invalid decrement rate: \n" . var_export($rate, true));
    }

    /**
     * Acquire a lock
     *
     * @param int $userId
     */
    private function acquireLock($userId) {
        if (is_int($userId)) {
            return
                $this->Memcache->add(
                    sprintf(self::MEMCACHE_USER_ENERGY_LOCK_KEY, $userId),
                    time(),
                    false,
                    self::MEMCACHE_USER_ENERGY_LOCK_EXPIRE
                )
            ;
        }

        throw new EnergyException("Invalid user id: \n" . var_export($userId, true));
    }

    /**
     * Release a lock
     *
     * @param int $userId
     */
    private function releaseLock($userId) {
        if (is_int($userId)) {
            return $this->Memcache->delete(sprintf(self::MEMCACHE_USER_ENERGY_LOCK_KEY, $userId));
        }

        throw new EnergyException("Invalid user id: \n" . var_export($userId, true));
    }
}

/**
 * EnergyException
 *
 * @package EncountersBundle
 */
class EnergyException extends \Exception {

}