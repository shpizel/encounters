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
            2 => 1500,
            3 => 4500,
            4 => 12500,
            5 => 21000,
            6 => 31000,
            7 => 43500,
            8 => 60000,
            9 => 80000,
            10 => 105000,
            11 => 135000,
            12 => 170000,
            13 => 215000,
            14 => 270000,
            15 => 340000,
            16 => 425000,
        )
    ;

    /**
     * Levels getter
     *
     * @return array
     */
    public function getLevels() {
        return self::$levels;
    }

    /**
     * Minimal energy getter
     *
     * @return int
     */
    public function getMinEnergy() {
        return min(self::$levels);
    }

    /**
     * Maximum energy getter
     *
     * @return int
     */
    public function getMaxEnergy() {
        return max(self::$levels);
    }

    /**
     * Minimal level getter
     *
     * @return int
     */
    public function getMinLevel() {
        return min(array_keys(self::$levels));
    }

    /**
     * Maximum level getter
     *
     * @return int
     */
    public function getMaxLevel() {
        return max(array_keys(self::$levels));
    }

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