<?php
namespace Mamba\EncountersBundle;

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
         * Ключ для хранения хитлиста
         *
         * @var str
         */
        REDIS_HASH_USER_HITLIST_KEY = "hitlist_by_%d"
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
     * @param int $period days
     * @return mixed
     */
    public function get($userId, $period = 1) {
        if (!is_int($userId)) {
            throw new HitlistException("Invalid user id: \n" . var_export($userId, true));
        }

        if (!is_int($period)) {
            throw new HitlistException("Invalid period: \n" . var_export($period, true));
        }

        $this->Redis->multi();
        for ($i=0;$i<$period*24;$i++) {
            $this->Redis->hGet(sprintf(self::REDIS_HASH_USER_HITLIST_KEY, $userId), date('YmdH', strtotime("-$i hours")));
        }

        $hitsArray = array_filter($this->Redis->exec(), function($item) {
            return (bool) $item;
        });

        return array_sum($hitsArray);
    }

    /**
     * Atomic increment
     *
     * @param int $userId
     * @param int $rate
     */
    public function incr($userId, $rate = 1) {
        if (!is_int($userId)) {
            throw new HitlistException("Invalid user id: \n" . var_export($userId, true));
        }

        if (!is_int($rate)) {
            throw new HitlistException("Invalid rate: \n" . var_export($rate, true));
        }

        return $this->Redis->hIncrBy(sprintf(self::REDIS_HASH_USER_HITLIST_KEY, $userId), date('YmdH') , $rate);
    }
}

/**
 * HitlistException
 *
 * @package EncountersBundle
 */
class HitlistException extends \Exception {

}