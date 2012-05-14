<?php
namespace Mamba\EncountersBundle\Helpers;

use Mamba\RedisBundle\Redis;

/**
 * Variables
 *
 * @package EncountersBundle
 */
class Variables extends Helper {

    const

        /**
         * Ключ для хранения переменных
         *
         * @var str
         */
        REDIS_HASH_USER_VARIABLES_KEY = "variables_by_%d"
    ;

    private static

        /**
         * Допустимые переменные и их ttl
         *
         * @var array
         */
        $options = array(
            /** Скрытие рыжего блока - у вас оч низкая популярность */
            'search_no_popular_block_hidden'  => 86400,

            /** когда последний раз проверялись поисковые предпочтения */
            'search_preferences_last_checked' => 0,

            /** Скрытие нотификаций */
            'notification_hidden'             => 0,

            /** последний заход на страницу welcome */
            'lastaccess'                      => 0,

            /** последние голосования */
            'last_incoming_decision'          => 0,
            'last_outgoing_decision'          => 0,

            /** нотификации */
            'last_notification_sent'          => 0,
            'last_notification_metrics'       => 0,

            /** ачивка */
            'last_achievement_metrics'        => 0,
        )
    ;

    /**
     * Variable getter
     *
     * @param int $userId
     * @param string $key
     * @return mixed
     */
    public function get($userId, $key) {
        if (!is_int($userId)) {
            throw new VariablesException("Invalid user id: \n" . var_export($userId, true));
        }

        if (key_exists($key, self::$options)) {
            if ($result = $this->getRedis()->hGet(sprintf(self::REDIS_HASH_USER_VARIABLES_KEY, $userId), $key)) {
                $result = json_decode($result, true);
                if ($result['expires'] > time() || !$result['expires']) {
                    return $result['data'];
                }
            }
        }
    }

    /**
     * All variables getter
     *
     * @param int $userId
     * @return array
     */
    public function getAll($userId) {
        if (!is_int($userId)) {
            throw new VariablesException("Invalid user id: \n" . var_export($userId, true));
        }

        return $this->getRedis()->hGetAll(sprintf(self::REDIS_HASH_USER_VARIABLES_KEY, $userId));
    }

    /**
     * Variable setter
     *
     * @param int $userId
     * @param string $key
     * @param mixed $data
     */
    public function set($userId, $key, $data) {
        if (!is_int($userId)) {
            throw new VariablesException("Invalid user id: \n" . var_export($userId, true));
        }

        if (key_exists($key, self::$options)) {
            return
                false !== $this->getRedis()->hSet(
                    sprintf(self::REDIS_HASH_USER_VARIABLES_KEY, $userId),
                    $key,
                    json_encode(
                        array(
                            'expires' => self::$options[$key] ? (time() + self::$options[$key]) : 0,
                            'data'    => $data,
                        )
                    )
                )
            ;
        }
    }


}

/**
 * VariablesException
 *
 * @package EncountersBundle
 */
class VariablesException extends \Exception {

}