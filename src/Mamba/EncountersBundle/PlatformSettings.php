<?php
namespace Mamba\EncountersBundle;

use Mamba\RedisBundle\Redis;

/**
 * PlatformParams
 *
 * @package EncountersBundle
 */
class PlatformSettings {

    const

        /**
         * Ключ для хранения хеша настроек платформы
         *
         * @var str
         */
        REDIS_HASH_USERS_PLATFORM_PARAMS_KEY = "users_platform_params",

        /**
         * Ключ для хранения хеша последних обращений к платформенному интерфейсу
         *
         * @var str
         */
        REDIS_HASH_USERS_PLATFORM_LAST_QUERY_TIME_KEY = "users_platform_last_query"
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
     * Platform params getter
     *
     * @param int $userId
     * @return mixed
     */
    public function get($userId) {
        if (is_int($userId)) {
            return $this->Redis->hGet(self::REDIS_HASH_USERS_PLATFORM_PARAMS_KEY, $userId);
        }

        throw new PlatformParamsException("Invalid user id: \n" . var_export($userId, true));
    }

    /**
     * Platform params setter
     *
     * @param array $platformParams
     * @return mixed
     */
    public function set(array $platformParams) {
        if (isset($platformParams['oid']) && ($mambaUserId = (int)$platformParams['oid'])) {
            if (isset($platformParams['auth_key'])) {
                unset($platformParams['auth_key']);
            }

            return $this->Redis->hSet(self::REDIS_HASH_USERS_PLATFORM_PARAMS_KEY, $mambaUserId, $platformParams);
        }

        throw new PlatformParamsException("Invalid platform params: \n" . var_export($platformParams, true));
    }

    /**
     * Устанавливает время последнего обращения к API
     *
     * @param int $userId
     * @param int|null $timestamp
     * @return mixed
     */
    public function setLastQueryTime($userId, $timestamp = null) {
        if (!$timestamp) {
            $timestamp = time();
        }

        if (!is_int($timestamp)) {
            throw new PlatformParamsException("Invalid timestamp: \n" . var_export($timestamp, true));
        }

        if (!is_int($userId)) {
            throw new PlatformParamsException("Invalid user id: \n" . var_export($userId, true));
        }

        return $this->Redis->hSet(self::REDIS_HASH_USERS_PLATFORM_LAST_QUERY_TIME_KEY, $userId, $timestamp);
    }

    /**
     * Устанавливает время последнего обращения к API
     *
     * @param int $userId
     * @return int
     */
    public function getLastQueryTime($userId) {
        if (is_int($userId)) {
            return $this->Redis->hGet(self::REDIS_HASH_USERS_PLATFORM_LAST_QUERY_TIME_KEY, $userId);
        }

        throw new PlatformParamsException("Invalid user id: \n" . var_export($userId, true));
    }
}

/**
 * PlatformParamsException
 *
 * @package EncountersBundle
 */
class PlatformParamsException extends \Exception {

}