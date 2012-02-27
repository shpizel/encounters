<?php
namespace Mamba\EncountersBundle\Helpers;

use Mamba\RedisBundle\Redis;
use Mamba\EncountersBundle\Entity\Energy;

/**
 * Energy
 *
 * @package EncountersBundle
 */
class EnergyHelper extends Helper {

    const

        /**
         * Энергия по умолчанию
         *
         * @var int
         */
        DEFAULT_ENERGY = 128,

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
        MAXIMUM_ENERGY = 2048
    ;

    /**
     * Energy getter
     *
     * @param int $userId
     * @return mixed
     */
    public function get($userId) {
        if (!is_int($userId)) {
            throw new EnergyHelperException("Invalid user id: \n" . var_export($userId, true));
        }

        if ($energy = $this->getDoctrine()->getRepository('EncountersBundle:Energy')->find($userId)) {
            return $energy->getEnergy();
        } else {
            try {
                $energy = new Energy();
                $energy->setUserId($userId);
                $energy->setEnergy(self::DEFAULT_ENERGY);

                $em = $this->getDoctrine()->getEntityManager();
                $em->persist($energy);
                $em->flush();
            } catch (\PDOException $e) {
                //pass
            }
        }

        return self::DEFAULT_ENERGY;
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
            throw new EnergyHelperException("Invalid user id: \n" . var_export($userId, true));
        }

        if (is_int($energy) && $energy >= self::MINIMUM_ENERGY && $energy <= self::MAXIMUM_ENERGY) {
            if ($energyObject = $this->getDoctrine()->getRepository('EncountersBundle:Energy')->find($userId)) {
                $energyObject->setEnergy($energy);

            } else {

            }
        }

        throw new EnergyHelperException("Invalid energy: \n" . var_export($energy, true));
    }

    /**
     * Atomic increment
     *
     * @param int $userId
     * @param int $rate
     */
    public function incr($userId, $rate = 1) {
        if (!is_int($userId)) {
            throw new EnergyHelperException("Invalid user id: \n" . var_export($userId, true));
        }

        if (!is_int($rate)) {
            throw new EnergyHelperException("Invalid increment rate: \n" . var_export($rate, true));
        }

        $incrementResult = $this->getRedis()->hIncrBy(self::REDIS_HASH_USERS_ENERGIES_KEY, $userId, $rate);
        if ($incrementResult < self::MINIMUM_ENERGY) {
            return $this->set($userId, $incrementResult = self::MINIMUM_ENERGY);
        } elseif ($incrementResult > self::MAXIMUM_ENERGY) {
            return $this->set($userId, $incrementResult = self::MAXIMUM_ENERGY);
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
            throw new EnergyHelperException("Invalid decrement rate: \n" . var_export($rate, true));
        }

        return $this->incr($userId, $rate * -1);
    }
}

/**
 * EnergyHelperException
 *
 * @package EncountersBundle
 */
class EnergyHelperException extends \Exception {

}