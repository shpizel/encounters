<?php
namespace Mamba\EncountersBundle\Helpers;

use Mamba\RedisBundle\Redis;

/**
 * Counters
 *
 * @package EncountersBundle
 */
class Counters {

    const

        /**
         * Ключ для хранения хитлиста
         *
         * @var str
         */
        REDIS_HASH_USER_COUNTERS_KEY = "counters_by_%d"
    ;

    private

        /**
         * Redis
         *
         * @var Redis
         */
        $Redis = null
    ;

    /**
     * Конструктор
     *
     * @param \Mamba\RedisBundle\Redis $Redis
     */
    public function __construct(Redis $Redis) {
        $this->Redis = $Redis;
    }

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

        return $this->Redis->hGet(sprintf(self::REDIS_HASH_USER_COUNTERS_KEY, $userId), $key);
    }

    /**
     * Counter increment
     *
     * @param int $userId
     * @param string $key
     * @param int $rate
     */
    public function incr($userId, $key, $rate) {
        if (!is_int($userId)) {
            throw new CountersException("Invalid user id: \n" . var_export($userId, true));
        }

        if (!is_int($rate)) {
            throw new CountersException("Invalid rate: \n" . var_export($rate, true));
        }

        return $this->Redis->hSet(sprintf(self::REDIS_HASH_USER_COUNTERS_KEY, $userId), $key, $rate);
    }
}

/**
 * CountersException
 *
 * @package EncountersBundle
 */
class CountersException extends \Exception {

}