<?php
namespace Mamba\EncountersBundle;

use Mamba\EncountersBundle\EncountersBundle;
use Mamba\RedisBundle\Redis;
use Mamba\MemcacheBundle\Memcache;

/**
 * Hitlist
 *
 * @package EncountersBundle
 */
class Hitlist {

    const

        /**
         * Ключ для хранения хеша количества хитов в сутки
         *
         * @var str
         */
        REDIS_HASH_USERS_HITLIST_COUNTS_KEY = "users_hitlist_counts"
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
     * @param $userId
     * @return null
     */
    public function __construct(Redis $Redis) {
        $this->Redis = $Redis;
    }

    /**
     * Hitlist getter
     *
     * @param int $userId
     * @return mixed
     */
    public function get($userId) {
        return $this->Redis->hGet(self::REDIS_HASH_USERS_HITLIST_COUNTS_KEY, $userId);
    }

    /**
     * Hitlist setter
     *
     * @param int $userId
     * @param int $value
     * @return mixed
     */
    public function set($userId, $value) {
        if (is_int($value)) {
            return $this->Redis->hSet(self::REDIS_HASH_USERS_HITLIST_COUNTS_KEY, $userId, $value);
        }

        throw new HitlistException("Invalid hits count: \n" . var_export($value, true));
    }

    /**
     * Atomic increment
     *
     * @param int $userId
     * @param int $rate
     */
    public function incr($userId, $rate = 1) {
        if (is_int($userId) && is_int($rate)) {
            return $this->Redis->hIncrBy(self::REDIS_HASH_USERS_HITLIST_COUNTS_KEY, $userId, $rate);
        }

        throw new HitlistException("Invalid increment rate: \n" . var_export($rate, true));
    }
}

/**
 * HitlistException
 *
 * @package EncountersBundle
 */
class HitlistException extends \Exception {

}