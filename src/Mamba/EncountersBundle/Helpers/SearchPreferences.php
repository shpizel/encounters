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
        if (false !== $data = $this->Redis->hGet(self::REDIS_HASH_USERS_SEARCH_PREFERENCES_KEY, $userId)) {
            return json_decode($data, true);
        }

        return false;
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
            $data['changed'] = time();
            return $this->Redis->hSet(self::REDIS_HASH_USERS_SEARCH_PREFERENCES_KEY, $userId, json_encode($data));
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