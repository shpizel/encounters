<?php
namespace Mamba\EncountersBundle\Helpers;

use Core\RedisBundle\Redis;

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
        DEFAULT_CHARGE = 0,

        /**
         * Ключ для хранения зарядки батарейки
         *
         * @var str
         */
        REDIS_USER_BATTERY_KEY = "battery_by_%d"
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

        $charge = $this->getRedis()->get(sprintf(self::REDIS_USER_BATTERY_KEY, $userId));
        if (false === $charge) {
            $this->set($userId, $charge = self::DEFAULT_CHARGE);
        }
        return (int) $charge;
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
            return $this->getRedis()->set(sprintf(self::REDIS_USER_BATTERY_KEY, $userId), $charge);
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

        $Stats = new Stats($this->Container);
        if ($rate < 0) {
            $Stats->incr("battery-decr", abs($rate));
        } else {
            $Stats->incr("battery-incr", abs($rate));
        }

        $incrementResult = $this->getRedis()->incrBy(sprintf(self::REDIS_USER_BATTERY_KEY, $userId), $rate);
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