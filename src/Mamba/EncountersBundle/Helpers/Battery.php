<?php
namespace Mamba\EncountersBundle\Helpers;

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
        LEVELDB_USER_BATTERY_KEY = "encounters:battery:%d"
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

        $Leveldb = $this->getLeveldb();

        $Request = $Leveldb->get($key = sprintf(self::LEVELDB_USER_BATTERY_KEY, $userId));
        $Leveldb->execute();

        $result = $Request->getResult();
        $charge = false;

        if (isset($result[$key])) {
            $charge = (int) $result[$key];
        }

        if (false == $charge) {
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
             $Leveldb = $this->getLeveldb();
            $Request = $Leveldb->set(array(
                $key = sprintf(self::LEVELDB_USER_BATTERY_KEY, $userId) => $charge
            ));

            $Leveldb->execute();

            $result = $Request->getResult();
            if ($result === true) {
                return $charge;
            } else {
                throw new BatteryException("Could not set energy {$userId}=>{$charge}");
            }
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

        $Leveldb = $this->getLeveldb();
        $Request = $Leveldb->inc_add(
            array(
                $key = sprintf(self::LEVELDB_USER_BATTERY_KEY, $userId) => $rate,
            ),
            array(
                $key => 0,
            )
        );

        $Leveldb->execute();

        $result = $Request->getResult();
        $incrementResult = 0;
        if (isset($result[$key])) {
            $incrementResult = (int) $result[$key];
        }

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