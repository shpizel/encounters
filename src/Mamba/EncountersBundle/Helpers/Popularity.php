<?php
namespace Mamba\EncountersBundle\Helpers;

use Mamba\EncountersBundle\Energy;

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
        if (is_int($energy) && $energy >= Energy::MINIMUM_ENERGY && $energy <= Energy::MAXIMUM_ENERGY) {
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