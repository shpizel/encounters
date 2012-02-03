<?php
namespace Mamba\EncountersBundle;

use Mamba\EncountersBundle\Enegy;

/**
 * Popularity
 *
 * @package EncountersBundle
 */
class Popularity {

    const

        /**
         * База логарифма
         *
         * @var float
         */
        LOG_BASE = 2.0
    ;

    /**
     * Energy -> Popularity converter
     *
     * @param int $energy
     * @return float
     */
    public static function getPopularity($energy, $base = self::LOG_BASE) {
        if (is_int($energy) && $energy >= Enegy::MINIMUM_ENERGY && $energy <= Enegy::MAXIMUM_ENERGY) {
            return log($energy, $base);
        }

        throw new PopularityException("Invalid energy rate: \n" . var_export($energy, true));
    }
}

/**
 * PopularityException
 *
 * @package EncountersBundle
 */
class PopularityException extends \Exception {

}