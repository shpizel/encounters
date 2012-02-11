<?php
namespace Mamba\EncountersBundle\Helpers;

use Mamba\RedisBundle\Redis;

/**
 * Counters
 *
 * @package EncountersBundle
 */
class Counters extends Helper {

    const

        /**
         * Ключ для хранения хитлиста
         *
         * @var str
         */
        REDIS_HASH_USER_CUSTOM_COUNTER_KEY = "%s_by_%d"
    ;

    /**
     * Counter getter
     *
     * @param int $userId
     * @param string $key
     * @return mixed
     */
    public function get($userId, $key) {
        if (!is_int($userId)) {
            throw new CountersException("Invalid user id: \n" . var_export($userId, true));
        }

        return $this->getRedis()->get(sprintf(self::REDIS_HASH_USER_CUSTOM_COUNTER_KEY, $key, $userId));
    }

    /**
     * Counter increment
     *
     * @param int $userId
     * @param string $key
     * @param int $rate
     */
    public function incr($userId, $key, $rate = 1) {
        if (!is_int($userId)) {
            throw new CountersException("Invalid user id: \n" . var_export($userId, true));
        }

        if (!is_int($rate)) {
            throw new CountersException("Invalid rate: \n" . var_export($rate, true));
        }

        return $this->getRedis()->incr(sprintf(self::REDIS_HASH_USER_CUSTOM_COUNTER_KEY, $key, $userId), $rate);
    }
}

/**
 * CountersException
 *
 * @package EncountersBundle
 */
class CountersException extends \Exception {

}