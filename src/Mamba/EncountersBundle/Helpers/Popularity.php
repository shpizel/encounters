<?php
namespace Mamba\EncountersBundle\Helpers;

use Mamba\EncountersBundle\Energy;

/**
 * Popularity
 *
 * @package EncountersBundle
 */
class Popularity extends Helper {

    const

        /**
         * Делитель энергии
         *
         * @var float
         */
        DIVIDER = 2.0
    ;

    /**
     * Energy -> Popularity converter
     *
     * @param int $energy
     * @return float
     */
    public static function getPopularity($energy, $divider = self::DIVIDER) {
        if (is_int($energy) && $energy >= Energy::MINIMUM_ENERGY && $energy <= Energy::MAXIMUM_ENERGY) {
            return $energy / $divider;
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