<?php
namespace Mamba\EncountersBundle;

use Mamba\EncountersBundle\EncountersBundle;
use Mamba\RedisBundle\Redis;
use Mamba\MemcacheBundle\Memcache;

/**
 * Battery
 *
 * @package EncountersBundle
 */
class Battery {

    const

        /**
         * Минимальный заряд
         *
         * @var int
         */
        MINIMUM_CHARGE = 0,

        /**
         * Максимальный заряд
         *
         * @var int
         */
        MAXIMUM_CHARGE = 5,

        /**
         * Ключ для хранения хеша энергий
         *
         * @var str
         */
        REDIS_HASH_USERS_BATTERY_CHARGES_KEY = "users_battery_chargeы",

        /**
         * Ключ для хранения блокировки изменения заряда батарейки
         *
         * @var string
         */
        MEMCACHE_USER_BATTERY_CHARGE_LOCK_KEY = "user_%d_battery_lock_key",

        /**
         * Время жизнь блокировки изменения заряда батарейки
         *
         * @var int
         */
        MEMCACHE_USER_BATTERY_CHARGE_LOCK_EXPIRE = 30
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
     * Charge getter
     *
     * @param int $userId
     * @return mixed
     */
    public function get($userId) {
        return $this->Redis->hGet(self::REDIS_HASH_USERS_BATTERY_CHARGES_KEY, $userId);
    }

    /**
     * Charge setter
     *
     * @param int $userId
     * @param int $value
     * @return mixed
     */
    public function set($userId, $value) {
        if (is_int($value) && $value >= self::MINIMUM_CHARGE && $value <= self::MAXIMUM_CHARGE) {
            return $this->Redis->hSet(self::REDIS_HASH_USERS_BATTERY_CHARGES_KEY, $userId, $value);
        }

        throw new BatteryException("Invalid charge: \n" . var_export($value, true));
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

                $currentBatteryCharge = $this->Redis->hGet(self::REDIS_HASH_USERS_BATTERY_CHARGES_KEY, $userId);
                $updatedBatteryCharge = $currentBatteryCharge + $rate;

                if ($updatedBatteryCharge >= self::MINIMUM_CHARGE && $updatedBatteryCharge <= self::MAXIMUM_CHARGE) {
                    return $this->set($userId, $updatedBatteryCharge);
                } else {
                    $this->releaseLock($userId);
                    throw new BatteryException("Invalid increment result: \n" . var_export($updatedBatteryCharge, true));
                }

                $this->releaseLock($userId);
            }

            throw new BatteryException("Could not obtain a lock");
        }

        throw new BatteryException("Invalid increment rate: \n" . var_export($rate, true));
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

                $currentBatteryCharge = $this->Redis->hGet(self::REDIS_HASH_USERS_BATTERY_CHARGES_KEY, $userId);
                $updatedBatteryCharge = $currentBatteryCharge - $rate;

                if ($updatedBatteryCharge >= self::MINIMUM_CHARGE && $updatedBatteryCharge <= self::MAXIMUM_CHARGE) {
                    return $this->set($userId, $updatedBatteryCharge);
                } else {
                    $this->releaseLock($userId);
                    throw new BatteryException("Invalid decrement result: \n" . var_export($updatedBatteryCharge, true));
                }

                $this->releaseLock($userId);
            }

            throw new BatteryException("Could not obtain a lock");
        }

        throw new BatteryException("Invalid decrement rate: \n" . var_export($rate, true));
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
                    sprintf(self::MEMCACHE_USER_BATTERY_CHARGE_LOCK_KEY, $userId),
                    time(),
                    false,
                    self::MEMCACHE_USER_BATTERY_CHARGE_LOCK_EXPIRE
                )
            ;
        }

        throw new BatteryException("Invalid user id: \n" . var_export($userId, true));
    }

    /**
     * Release a lock
     *
     * @param int $userId
     */
    private function releaseLock($userId) {
        if (is_int($userId)) {
            return $this->Memcache->delete(sprintf(self::MEMCACHE_USER_BATTERY_CHARGE_LOCK_KEY, $userId));
        }

        throw new BatteryException("Invalid user id: \n" . var_export($userId, true));
    }
}

/**
 * BatteryException
 *
 * @package EncountersBundle
 */
class BatteryException extends \Exception {

}