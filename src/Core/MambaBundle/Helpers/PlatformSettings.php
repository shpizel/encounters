<?php
namespace Core\MambaBundle\Helpers;

use Core\RedisBundle\Redis;

/**
 * Class PlatformSettings
 * 
 * @package Core\MambaBundle\Helpers
 */
class PlatformSettings {

    protected

        /**
         * Redis
         *
         * @var Redis
         */
        $Redis = null,

        /**
         * Статический кеш
         *
         * @var array
         */
        $staticCache = [
            'settings'  => [],
            'lastquery' => [],
        ]
    ;

    const

        /**
         * Ключ для хранения хеша настроек платформы
         *
         * @var str
         */
        REDIS_USER_PLATFORM_SETTINGS_KEY = "platform_settings_by_%d",

        /**
         * Ключ для хранения хеша последних обращений к платформенному интерфейсу
         *
         * @var str
         */
        REDIS_USER_PLATFORM_LAST_QUERY_TIME_KEY = "platform_last_query_by_%d"
    ;

    /**
     * Конструктор
     *
     *
     */
    public function __construct(Redis $Redis) {
        $this->Redis = $Redis;
    }

    /**
     * Redis getter
     *
     * @return Redis
     */
    public function getRedis() {
        return $this->Redis;
    }

    /**
     * Platform params getter
     *
     * @param int $userId
     * @return mixed
     */
    public function get($userId) {
        if (!is_int($userId)) {
            throw new PlatformParamsException("Invalid user id: \n" . var_export($userId, true));
        }

        if (isset($this->staticCache['settings'][$userId])) {
            return $this->staticCache['settings'][$userId];
        }

        if (false !== $data = $this->getRedis()->get(sprintf(self::REDIS_USER_PLATFORM_SETTINGS_KEY, $userId))) {
            return $this->staticCache['settings'][$userId] = json_decode($data, true);
        }
    }

    /**
     * Platform params setter
     *
     * @param array $platformParams
     * @return mixed
     */
    public function set(array $platformParams) {
        if (isset($platformParams['oid']) && ($userId = $platformParams['oid'] = (int)$platformParams['oid'])) {
            if (isset($platformParams['auth_key'])) {
                unset($platformParams['auth_key']);
            }

            $this->getRedis()->set(sprintf(self::REDIS_USER_PLATFORM_SETTINGS_KEY, $userId), json_encode($platformParams));
            $this->setLastQueryTime($userId);

            $this->staticCache['settings'][$userId] = $platformParams;

            return;
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

        $this->staticCache['lastquery'][$userId] = $timestamp;

        return $this->getRedis()->set(sprintf(self::REDIS_USER_PLATFORM_LAST_QUERY_TIME_KEY, $userId), $timestamp);
    }

    /**
     * Запрашивает время последнего обращения к API
     *
     * @param int $userId
     * @return int
     */
    public function getLastQueryTime($userId) {
        if (is_int($userId)) {
            $ret = $this->getRedis()->get(sprintf(self::REDIS_USER_PLATFORM_LAST_QUERY_TIME_KEY, $userId));
            if ($ret) {
                $this->staticCache['lastquery'][$userId] = $ret;
            }

            return $ret;
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