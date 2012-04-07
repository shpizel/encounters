<?php
namespace Mamba\EncountersBundle\Helpers;

use Mamba\EncountersBundle\Helpers\Energy;

/**
 * Popularity
 *
 * @package EncountersBundle
 */
class Popularity extends Helper {

    public static

        /**
         * Уровни
         *
         * @var array
         */
        $levels = array(
            1 => 0,
            2 => 15,
            3 => 45,
            4 => 125,
            5 => 210,
            6 => 310,
            7 => 435,
            8 => 600,
            9 => 800,
            10 => 1050,
            11 => 1350,
            12 => 1700,
            13 => 2150,
            14 => 2700,
            15 => 3400,
            16 => 4250,
        )
    ;

    /**
     * Energy -> level
     *
     * @param int $energy
     */
    public function getLevel($energy) {
        foreach (self::$levels as $level=>$exp) {
            if ($exp == $energy) {
                return $level;
            } elseif ($exp > $energy) {
                return $level - 1;
            }
        }

        return max(array_keys(self::$levels));
    }

    /**
     * Get info
     *
     * @return array()
     */
    public function getInfo($energy) {
        $max = max(array_keys(self::$levels));
        $lvl = $this->getLevel($energy);

        return array(
            'level'  => $lvl,
            'energy' => $energy,
            'next'   => $lvl >= $max ? 0 : self::$levels[$lvl + 1],
            'prev'   => self::$levels[$lvl],
        );
    }
}

/**
 * PopularityException
 *
 * @package EncountersBundle
 */
class PopularityException extends \Exception {

}