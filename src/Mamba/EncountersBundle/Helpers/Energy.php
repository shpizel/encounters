<?php
namespace Mamba\EncountersBundle\Helpers;

use Mamba\EncountersBundle\EncountersBundle;
use Mamba\EncountersBundle\Helpers\Popularity;

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
         * Ключ для хранения энергии
         *
         * @var str
         */
        LEVELDB_USER_ENERGY_KEY = "encounters:energy:%d"
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

        $Leveldb = $this->getLeveldb();

        $Request = $Leveldb->get($key = sprintf(self::LEVELDB_USER_ENERGY_KEY, $userId));
        $Leveldb->execute();

        $result = $Request->getResult();
        $energy = false;

        if (isset($result[$key])) {
            $energy = (int) $result[$key];
        }

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

        $Leveldb = $this->getLeveldb();

        if (is_int($energy) && $energy >= self::MINIMUM_ENERGY && $energy <= max(Popularity::$levels) - 1 /** нахуя тут минус 1 неясно но хуй с ним */) {
            $Request = $Leveldb->set(
                array(
                    $key = sprintf(self::LEVELDB_USER_ENERGY_KEY, $userId) => $energy
                )
            );

            $Leveldb->execute();

            $result = $Request->getResult();

            $this->getMemcache()->add("energy_update_lock_by_user_" . $userId, time(), 750) &&  $this->getGearman()->getClient()->doHighBackground(
                EncountersBundle::GEARMAN_DATABASE_ENERGY_UPDATE_FUNCTION_NAME,
                serialize(
                    array(
                        'user_id' => $userId,
                        'energy'  => $energy,
                    )
                )
            );

            if ($result === true) {
                return $energy;
            } else {
                throw new EnergyException("Could not set energy {$userId}=>{$energy}");
            }
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

        $Leveldb = $this->getLeveldb();
        $Request = $Leveldb->inc_add(
            array(
                $key = sprintf(self::LEVELDB_USER_ENERGY_KEY, $userId) => $rate,
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
        } elseif ($incrementResult > ($max = max(Popularity::$levels) - 1)) {
            $result = $this->set($userId, $incrementResult = $max);

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