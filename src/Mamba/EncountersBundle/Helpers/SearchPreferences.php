<?php
namespace Mamba\EncountersBundle\Helpers;

use Mamba\RedisBundle\Redis;

/**
 * Preferences
 *
 * @package EncountersBundle
 */
class SearchPreferences extends Helper {

    const

        /**
         * Ключ для хранения хеша пользовательских настроек поиска
         *
         * @var str
         */
        REDIS_HASH_USERS_SEARCH_PREFERENCES_KEY = "users_search_preferences"
    ;

    /**
     * Preferences getter
     *
     * @param int $userId
     * @return mixed
     */
    public function get($userId) {
        if (false !== $data = $this->getRedis()->hGet(self::REDIS_HASH_USERS_SEARCH_PREFERENCES_KEY, $userId)) {
            return json_decode($data, true);
        }

        return false;
    }

    /**
     * Preferences setter
     *
     * @param int $userId
     * @param array $data
     * @return mixed
     */
    public function set($userId, array $data) {
        if ($data) {
            $data['changed'] = time();
            return $this->getRedis()->hSet(self::REDIS_HASH_USERS_SEARCH_PREFERENCES_KEY, $userId, json_encode($data));
        }

        throw new SearchPreferencesException("Invalid data: \n" . var_export($data, true));
    }

    /**
     * Preferences exists getter
     *
     * @param int $userId
     * @return boolean
     */
    public function exists($userId) {
        return $this->getRedis()->hExists(self::REDIS_HASH_USERS_SEARCH_PREFERENCES_KEY, $userId);
    }
}

/**
 * SearchPreferencesException
 *
 * @package EncountersBundle
 */
class SearchPreferencesException extends \Exception {

}