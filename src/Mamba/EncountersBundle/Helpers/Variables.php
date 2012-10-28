<?php
namespace Mamba\EncountersBundle\Helpers;

use Core\RedisBundle\Redis;

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
        REDIS_HASH_USER_VARIABLES_KEY = "variables_by_%d",

        /**
         * Внутренний тип переменной (только для внутреннего использования)
         *
         * @var int
         */
        VARIABLE_INTERNAL_TYPE = 1,

        /**
         * Внешний тип переменной (можно редактировать через AJAX)
         *
         * @var int
         */
        VARIABLE_EXTERNAL_TYPE = 2,

        /**
         * Смешанный тип переменной (можно и изнутри и снаружи работать)
         *
         * @var int
         */
        VARIABLE_BOTH_TYPE = 3
    ;

    private static

        /**
         * Доступные переменные в формате 'variable' => [type, ttl, validator lambda]
         *
         * @var array
         */
        $options
    ;

    public function __construct($Container) {
        parent::__construct($Container);

        self::$options = array(

            /** Скрытие рыжего блока - у вас оч низкая популярность */
            'search_no_popular_block_hidden' => array(
                'type' => self::VARIABLE_EXTERNAL_TYPE,
                'ttl'  => 86400,
                'validator' => function($variable) {
                    return true;
                },
            ),

            /** когда последний раз проверялись поисковые предпочтения */
            'search_preferences_last_checked' => array(
                'type' => self::VARIABLE_INTERNAL_TYPE,
                'ttl'  => 0,
                'validator' => function($variable) {
                    return $variable;
                },
            ),

            /** Скрытие нотификаций */
            'notification_hidden' => array(
                'type' => self::VARIABLE_EXTERNAL_TYPE,
                'ttl'  => 0,
                'validator' => function($variable) {
                    return true;
                },
            ),

            /** последний заход на страницу welcome */
            'lastaccess' => array(
                'type' => self::VARIABLE_INTERNAL_TYPE,
                'ttl'  => 0,
                'validator' => function($variable) {
                    return true;
                },
            ),

            /** последние голосования */
            'last_incoming_decision' => array(
                'type' => self::VARIABLE_INTERNAL_TYPE,
                'ttl'  => 0,
                'validator' => function($variable) {
                    return true;
                },
            ),

            'last_outgoing_decision' => array(
                'type' => self::VARIABLE_INTERNAL_TYPE,
                'ttl'  => 0,
                'validator' => function($variable) {
                    return true;
                },
            ),

            /** нотификации */
            'last_notification_sent' => array(
                'type' => self::VARIABLE_INTERNAL_TYPE,
                'ttl'  => 0,
                'validator' => function($variable) {
                    return true;
                },
            ),

            'last_notification_metrics' => array(
                'type' => self::VARIABLE_INTERNAL_TYPE,
                'ttl'  => 0,
                'validator' => function($variable) {
                    return true;
                },
            ),

            /** личка */
            'last_message_sent' => array(
                'type' => self::VARIABLE_INTERNAL_TYPE,
                'ttl'  => 604800 /** неделя = 7*24*3600 */,
                'validator' => function($variable) {
                    return true;
                },
            ),

            /** ачивка */
            'last_achievement_metrics' => array(
                'type' => self::VARIABLE_INTERNAL_TYPE,
                'ttl'  => 0,
                'validator' => function($variable) {
                    return true;
                },
            ),

            /** мульти приглашалка друзей */
            'last_multi_gift_shown' => array(
                'type' => self::VARIABLE_EXTERNAL_TYPE,
                'ttl'  => 86400,
                'validator' => function($variable) {
                    return true;
                },
            ),

            /** share */
            'sharing_enabled' => array(
                'type' => self::VARIABLE_EXTERNAL_TYPE,
                'ttl'  => 0,
                'validator' => function($variable) {
                    return true;
                },
            ),

            'sharing_reminder'=> array(
                'type' => self::VARIABLE_EXTERNAL_TYPE,
                'ttl'  => 86400,
                'validator' => function($variable) {
                    return true;
                },
            ),
        );
    }

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

        if (array_key_exists($key, self::$options)) {
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
     * Check type is external
     *
     * @param $key
     * @return bool
     */
    public function isExternal($key) {
        if (array_key_exists($key, self::$options)) {
            return in_array(self::$options[$key]['type'], array(self::VARIABLE_BOTH_TYPE, self::VARIABLE_EXTERNAL_TYPE));
        }
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

        if (array_key_exists($key, self::$options)) {
            $options = self::$options[$key];

            $ttl = (isset($options['ttl'])) ? $options['ttl'] : 0;
            $validator = (isset($options['validator'])) ? $options['validator'] : null;

            if ($validator && !$validator($data)) {
                throw new VariablesException("Invalid data for key {$key}:". PHP_EOL . "==data start==" . PHP_EOL . var_export($data, true) . PHP_EOL . "==data end==");
            }

            return
                false !== $this->getRedis()->hSet(
                    sprintf(self::REDIS_HASH_USER_VARIABLES_KEY, $userId),
                    $key,
                    json_encode(
                        array(
                            'expires' => $ttl ? (time() + $ttl) : 0,
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