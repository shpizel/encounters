<?php
namespace Mamba\EncountersBundle;

use Mamba\RedisBundle\Redis;

/**
 * CronLastAccess
 *
 * @package EncountersBundle
 */
class CronLastAccess {

    const

        /**
         * Ключ для хранения хитлиста
         *
         * @var str
         */
        REDIS_HASH_USER_CRON_LASTACCESS_KEY = "cron_by_%d"
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
     * LastAccess getter
     *
     * @param int $userId
     * @param int $cronId
     * @return mixed
     */
    public function get($userId, $cronId) {
        if (!is_int($userId)) {
            throw new CronLastAccessException("Invalid user id: \n" . var_export($userId, true));
        }

        if (!is_int($cronId)) {
            throw new CronLastAccessException("Invalid cron id: \n" . var_export($cronId, true));
        }

        return $this->Redis->hGet(sprintf(self::REDIS_HASH_USER_CRON_LASTACCESS_KEY, $userId), $cronId);
    }

    /**
     * LastAccess setter
     *
     * @param int $userId
     * @param int $cronId
     * @param int $lastAccess
     */
    public function set($userId, $cronId, $lastAccess) {
        if (!is_int($userId)) {
            throw new CronLastAccessException("Invalid user id: \n" . var_export($userId, true));
        }

        if (!is_int($cronId)) {
            throw new CronLastAccessException("Invalid cron id: \n" . var_export($cronId, true));
        }

        if (!is_int($lastAccess)) {
            throw new CronLastAccessException("Invalid last access: \n" . var_export($lastAccess, true));
        }

        return $this->Redis->hSet(sprintf(self::REDIS_HASH_USER_CRON_LASTACCESS_KEY, $userId), $cronId, $lastAccess);
    }
}

/**
 * CronLastAccessException
 *
 * @package EncountersBundle
 */
class CronLastAccessException extends \Exception {

}