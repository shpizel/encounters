<?php
namespace Mamba\EncountersBundle\Helpers;

/**
 * Declensions
 *
 * @package EncountersBundle
 */
class Declensions {

    /**
     * Get right declension by number
     *
     * @static
     * @param int $number
     * @param string $first
     * @param string $second
     * @param string $third
     *
     * @example Declensions::get($num, "новая оценка", "новые оценки", "новых оценок")
     *
     * @return string
     */
    public static function get($number, $first, $second, $third) {
        if ($number >= 11 && $number <= 20) {
            return $third;
        } elseif ($number % 10 == 0) {
            return $third;
        } elseif ($number % 10 == 1) {
            return $first;
        } elseif ($number % 10 < 5) {
            return $second;
        } else {
            return $third;
        }
    }
}

/**
 * DeclensionsException
 *
 * @package EncountersBundle
 */
class DeclensionsException extends \Exception {

}