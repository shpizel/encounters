<?php
namespace Mamba\EncountersBundle\Helpers;

use Mamba\RedisBundle\Redis;

/**
 * Preferences
 *
 * @package EncountersBundle
 */
class Preferences {

    const

        /**
         * Ключ для хранения хеша пользовательских настроек поиска
         *
         * @var str
         */
        REDIS_HASH_USERS_SEARCH_PREFERENCES_KEY = "users_search_preferences"
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
     * @return null
     */
    public function __construct(Redis $Redis) {
        $this->Redis = $Redis;
    }

    /**
     * Preferences getter
     *
     * @param int $userId
     * @return mixed
     */
    public function get($userId) {
        return $this->Redis->hGet(self::REDIS_HASH_USERS_SEARCH_PREFERENCES_KEY, $userId);
    }

    /**
     * Hitlist setter
     *
     * @param int $userId
     * @param array $data
     * @return mixed
     */
    public function set($userId, array $data) {
        if ($data) {
            return $this->Redis->hSet(self::REDIS_HASH_USERS_SEARCH_PREFERENCES_KEY, $userId, $data);
        }

        throw new PreferencesException("Invalid data: \n" . var_export($data, true));
    }
}

/**
 * PreferencesException
 *
 * @package EncountersBundle
 */
class PreferencesException extends \Exception {

}