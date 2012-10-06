<?php
namespace Mamba\EncountersBundle\Helpers;

use Core\RedisBundle\Redis;

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
        REDIS_USER_SEARCH_PREFERENCES_KEY = "search_preferences_by_%d"
    ;

    /**
     * Preferences getter
     *
     * @param int $userId
     * @return mixed
     */
    public function get($userId) {
        if (false !== $data = $this->getRedis()->get(sprintf(self::REDIS_USER_SEARCH_PREFERENCES_KEY, $userId))) {
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
            return $this->getRedis()->set(sprintf(self::REDIS_USER_SEARCH_PREFERENCES_KEY, $userId), json_encode($data));
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
        return $this->getRedis()->exists(sprintf(self::REDIS_USER_SEARCH_PREFERENCES_KEY, $userId));
    }
}

/**
 * SearchPreferencesException
 *
 * @package EncountersBundle
 */
class SearchPreferencesException extends \Exception {

}