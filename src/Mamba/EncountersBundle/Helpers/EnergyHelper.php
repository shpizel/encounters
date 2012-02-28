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

        if ($energyObject = $this->getDoctrine()->getRepository('EncountersBundle:Energy')->find($userId)) {
            return $energyObject->getEnergy();
        } else {
            $this->set($userId, self::DEFAULT_ENERGY);
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
            if ($energyObject = $this->getEnergyObjectByUserId($userId)) {
                $energyObject->setEnergy($energy);
                $this->getEntityManager()->flush();
            } else {
                $energyObject = new Energy();
                $energyObject->setUserId($userId);
                $energyObject->setEnergy($energy);

                $this->getEntityManager()->persist($energyObject);

                try {
                    return $this->getEntityManager()->flush();
                } catch (\PDOException $e) {
                    return;
                }
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

        $incrementResult = self::DEFAULT_ENERGY + $rate;
        if ($energyObject = $this->getEnergyObjectByUserId($userId)) {
            $incrementResult = $energyObject->getEnergy() + $rate;
            if ($incrementResult < self::MINIMUM_ENERGY) {
                $energyObject->setEnergy($incrementResult = self::MINIMUM_ENERGY);
            } elseif ($incrementResult > self::MAXIMUM_ENERGY) {
                $energyObject->setEnergy($incrementResult = self::MAXIMUM_ENERGY);
            } else {
                $energyObject->setEnergy($incrementResult);
            }

            $this->getEntityManager()->flush();
        } else {
            $this->set($userId, $incrementResult);
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

    /**
     * Energy object getter
     *
     * @param int $userId
     * @return Energy
     */
    private function getEnergyObjectByUserId($userId) {
        if (!is_int($userId)) {
            throw new EnergyHelperException("Invalid user id: \n" . var_export($userId, true));
        }

        return $this->getEntityManager()->getRepository('EncountersBundle:Energy')->find($userId);
    }
}

/**
 * EnergyHelperException
 *
 * @package EncountersBundle
 */
class EnergyHelperException extends \Exception {

}