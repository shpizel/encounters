<?php
namespace Mamba\EncountersBundle\Helpers;

use Mamba\EncountersBundle\EncountersBundle;

/**
 * Energy
 *
 * @package EncountersBundle
 */
class Energy extends Helper {

    const

        /**
         * Энергия по умолчанию
         *
         * @var int
         */
        DEFAULT_ENERGY = 0,

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
        MAXIMUM_ENERGY = 425000,

        /**
         * Ключ для хранения энергии
         *
         * @var str
         */
        REDIS_HASH_USERS_ENERGIES_KEY = "energies"
    ;

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

        $energy = $this->getRedis()->hGet(self::REDIS_HASH_USERS_ENERGIES_KEY, $userId);
        if (false === $energy) {
            $this->set($userId, $energy = self::DEFAULT_ENERGY);

            $this->getMemcache()->add("energy_update_lock_by_user_" . $userId, time(), 750) && $this->getGearman()->getClient()->doHighBackground(
                EncountersBundle::GEARMAN_DATABASE_ENERGY_UPDATE_FUNCTION_NAME,
                serialize(
                    array(
                        'user_id' => $userId,
                        'energy'  => $energy,
                    )
                )
            );
        }

        return (int) $energy;
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
            $result = $this->getRedis()->hSet(self::REDIS_HASH_USERS_ENERGIES_KEY, $userId, $energy);

            $this->getMemcache()->add("energy_update_lock_by_user_" . $userId, time(), 750) &&  $this->getGearman()->getClient()->doHighBackground(
                EncountersBundle::GEARMAN_DATABASE_ENERGY_UPDATE_FUNCTION_NAME,
                serialize(
                    array(
                        'user_id' => $userId,
                        'energy'  => $energy,
                    )
                )
            );

            return $result;
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

        $incrementResult = $this->getRedis()->hIncrBy(self::REDIS_HASH_USERS_ENERGIES_KEY, $userId, $rate);
        if ($incrementResult < self::MINIMUM_ENERGY) {
            $result =  $this->set($userId, $incrementResult = self::MINIMUM_ENERGY);

            $this->getMemcache()->add("energy_update_lock_by_user_" . $userId, time(), 750) &&  $this->getGearman()->getClient()->doHighBackground(
                EncountersBundle::GEARMAN_DATABASE_ENERGY_UPDATE_FUNCTION_NAME,
                serialize(
                    array(
                        'user_id' => $userId,
                        'energy'  => $incrementResult,
                    )
                )
            );

            return $result;
        } elseif ($incrementResult > self::MAXIMUM_ENERGY) {
            $result = $this->set($userId, $incrementResult = self::MAXIMUM_ENERGY);

            $this->getMemcache()->add("energy_update_lock_by_user_" . $userId, time(), 750) && $this->getGearman()->getClient()->doHighBackground(
                EncountersBundle::GEARMAN_DATABASE_ENERGY_UPDATE_FUNCTION_NAME,
                serialize(
                    array(
                        'user_id' => $userId,
                        'energy'  => $incrementResult,
                    )
                )
            );

            return $result;
        }

        $this->getMemcache()->add("energy_update_lock_by_user_" . $userId, time(), 750) &&  $this->getGearman()->getClient()->doHighBackground(
            EncountersBundle::GEARMAN_DATABASE_ENERGY_UPDATE_FUNCTION_NAME,
            serialize(
                array(
                    'user_id' => $userId,
                    'energy'  => $incrementResult,
                )
            )
        );

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