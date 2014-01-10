<?php
namespace Mamba\EncountersBundle\Helpers;

use Core\RedisBundle\Redis;
use Mamba\EncountersBundle\EncountersBundle;

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
        //LEVELDB_USER_SEARCH_PREFERENCES = "search_preferences_by_%d"
        LEVELDB_USER_SEARCH_PREFERENCES = "encounters:search-preferences:%d"
    ;

    /**
     * Preferences getter
     *
     * @param int $userId
     * @return mixed
     */
    public function get($userId) {
        $LevelDb = $this->getLeveldb();
        $Request = $LevelDb->get($leveldbKey = sprintf(self::LEVELDB_USER_SEARCH_PREFERENCES, $userId));
        $LevelDb->execute();
        if (($result = $Request->getResult()) && (isset($result[$leveldbKey]))) {
            return json_decode($result[$leveldbKey], true);
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
        $LevelDb = $this->getLeveldb();
        if ($data) {
            $data['changed'] = time();
            $Request = $LevelDb->set(array(
                $leveldbKey = sprintf(self::LEVELDB_USER_SEARCH_PREFERENCES, $userId) => json_encode($data),
            ));

            $LevelDb->execute();
            if ($Request->getResult() === true) {
                $this->getGearman()->getClient()->doLowBackground(
                    EncountersBundle::GEARMAN_DATABASE_USERS_SEARCH_PREFERENCES_UPDATE_FUNCTION_NAME,
                    serialize(
                        array(
                            'user_id' => $userId,
                            'time'    => time(),
                        )
                    )
                );

                return true;
            } else {
                throw new SearchPreferencesException("Could not set {$leveldbKey}");
            }
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
        $LevelDb = $this->getLeveldb();
        $Request = $LevelDb->get($leveldbKey = sprintf(self::LEVELDB_USER_SEARCH_PREFERENCES, $userId));
        $LevelDb->execute();

        if ($Request->getResult()) {
            return true;
        }

        return false;
    }
}

/**
 * SearchPreferencesException
 *
 * @package EncountersBundle
 */
class SearchPreferencesException extends \Exception {

}