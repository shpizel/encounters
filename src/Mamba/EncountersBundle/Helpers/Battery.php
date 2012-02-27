<?php
namespace Mamba\EncountersBundle\Helpers;

use Mamba\RedisBundle\Redis;

/**
 * Battery
 *
 * @package EncountersBundle
 */
class Battery extends Helper {

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
         * Заряд по-умолчанию
         *
         * @var int
         */
        DEFAULT_CHARGE = 1,

        /**
         * Ключ для хранения зарядки батарейки
         *
         * @var str
         */
        REDIS_HASH_USERS_BATTERY_CHARGES_KEY = "batteries"
    ;

    /**
     * Charge getter
     *
     * @param int $userId
     * @return mixed
     */
    public function get($userId) {
        if (!is_int($userId)) {
            throw new BatteryException("Invalid user id: \n" . var_export($userId, true));
        }

        $charge = $this->getRedis()->hGet(self::REDIS_HASH_USERS_BATTERY_CHARGES_KEY, $userId);
        if (false === $charge) {
            $this->set($userId, $charge = self::DEFAULT_CHARGE);
        }
        return $charge;
    }

    /**
     * Charge setter
     *
     * @param int $userId
     * @param int $charge
     * @return mixed
     */
    public function set($userId, $charge) {
        if (!is_int($userId)) {
            throw new BatteryException("Invalid user id: \n" . var_export($userId, true));
        }

        if (is_int($charge) && $charge >= self::MINIMUM_CHARGE && $charge <= self::MAXIMUM_CHARGE) {
            return $this->getRedis()->hSet(self::REDIS_HASH_USERS_BATTERY_CHARGES_KEY, $userId, $charge);
        }

        throw new BatteryException("Invalid charge: \n" . var_export($charge, true));
    }

    /**
     * Atomic increment
     *
     * @param int $userId
     * @param int $rate
     */
    public function incr($userId, $rate = 1) {
        if (!is_int($userId)) {
            throw new BatteryException("Invalid user id: \n" . var_export($userId, true));
        }

        if (!is_int($rate)) {
            throw new BatteryException("Invalid increment rate: \n" . var_export($rate, true));
        }

        $incrementResult = $this->getRedis()->hIncrBy(self::REDIS_HASH_USERS_BATTERY_CHARGES_KEY, $userId, $rate);
        if ($incrementResult < self::MINIMUM_CHARGE) {
            $this->set($userId, $incrementResult = self::MINIMUM_CHARGE);
        } elseif ($incrementResult > self::MAXIMUM_CHARGE) {
            $this->set($userId, $incrementResult = self::MAXIMUM_CHARGE);
        }

        return $incrementResult;
    }

    /**
     * Atomic decrement
     *
     * @param int $userId
     * @param int $rate
     */
    public function decr($userId, $rate = 1) {
        if (!is_int($rate)) {
            throw new BatteryException("Invalid rate: \n" . var_export($rate, true));
        }

        return $this->incr($userId, -$rate);
    }
}

/**
 * BatteryException
 *
 * @package EncountersBundle
 */
class BatteryException extends \Exception {

}