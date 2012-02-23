<?php
namespace Mamba\EncountersBundle\Helpers;

use Mamba\RedisBundle\Redis;

/**
 * Stats
 *
 * @package EncountersBundle
 */
class Stats extends Helper {

    const

        /**
         * Ключ для хранения хитлиста
         *
         * @var str
         */
        REDIS_HASH_STATS_KEY = "stats_by_%s"
    ;

    /**
     * Counter getter
     *
     * @param str $date
     * @param string $key
     * @return mixed
     */
    public function get($date, $key) {
        return $this->Redis->hGet(sprintf(self::REDIS_HASH_STATS_KEY, $date), $key);
    }

    /**
     * Counter increment
     *
     * @param string $key
     * @param int $rate
     */
    public function incr($key, $rate = 1) {
        if (!is_int($rate)) {
            throw new StatsException("Invalid rate: \n" . var_export($rate, true));
        }

        return $this->Redis->hIncrBy(sprintf(self::REDIS_HASH_STATS_KEY, date('dmy')), $key, $rate);
    }
}

/**
 * StatsException
 *
 * @package EncountersBundle
 */
class StatsException extends \Exception {

}