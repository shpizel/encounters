<?php
namespace Mamba\EncountersBundle\Helpers;

use Core\RedisBundle\Redis;
use Mamba\EncountersBundle\EncountersBundle;

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
        LEVELDB_USER_VARIABLE_KEY = 'encounters:variables:%d:%s',
    

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
            $LevelDb = $this->getLeveldb();
            $Request = $LevelDb->get($leveldbKey = sprintf(self::LEVELDB_USER_VARIABLE_KEY, $userId, $key));
            $LevelDb->execute();
            
            if (($result = $Request->getResult()) && isset($result[$leveldbKey])) {
                $result = $result[$leveldbKey];

                $result = json_decode($result, true);
                if ($result['expires'] > time() || !$result['expires']) {
                    return $result['data'];
                }
            }
        }
    }

    /**
     * Variables multi getter
     *
     * @param array $users
     * @param array $variables
     * @return mixed
     * @throws VariablesException
     */
    public function getMulti(array $users, array $variables = array()) {
        foreach ($users as $userId) {
            if (!is_int($userId)) {
                throw new VariablesException("Invalid user id: \n" . var_export($userId, true));
            }
        }

        if (!$variables) {
            $variables = array_keys(self::$options);
        }

        foreach ($variables as $index=>$key) {
            if (!array_key_exists($key, self::$options)) {
                unset($variables[$index]);
            }
        }

        $keys = [];
        foreach ($users as $userId) {
            foreach ($variables as $variable) {
                $leveldbKey = sprintf(self::LEVELDB_USER_VARIABLE_KEY, $userId, $variable);
                $keys[] = $leveldbKey;
            }
        }

        if (!$keys) return;

        $regexp = "!" . str_replace(array("%d", "%s"), array("(\\d+)", "(.*)$"), self::LEVELDB_USER_VARIABLE_KEY) . "!S";

        $LevelDb = $this->getLeveldb();
        $Request = $LevelDb->get($keys);
        $LevelDb->execute();

        if ($results = $Request->getResult()) {
            $ret = [];

            foreach ($results as $key=>$result) {
                if (preg_match($regexp, $key, $data)) {
                    $leveldbKey = array_pop($data);
                    $userId = array_pop($data);

                    if ($leveldbKey && $userId) {
                        $result = json_decode($result, true);
                        if ($result['expires'] > time() || !$result['expires']) {
                            if (!isset($ret[$userId])) {
                                $ret[$userId] = [];
                            }

                            $ret[$userId][$leveldbKey] = $result['data'];
                        }
                    }
                }
            }

            foreach ($ret as $userId=>$userVariables) {
                foreach ($variables as $variable) {
                    if (!isset($userVariables[$variable])) {
                        $ret[$userId][$variable] = null;
                    }
                }
            }

            return $ret;
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

        $LevelDb = $this->getLeveldb();
        $Request = $LevelDb->get_range(
            $leveldbKey = sprintf(self::LEVELDB_USER_VARIABLE_KEY, $userId, ''),
            null,
            100
        );

        $LevelDb->execute();
        if ($result = $Request->getResult()) {
            foreach ($result as $key=>$val) {
                if (strpos($key, $leveldbKey) === false) {
                    unset($result[$key]);
                }
            }

            return $result;
        }
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

            $LevelDb = $this->getLeveldb();
            $Request = $LevelDb->set(array(
                $leveldbKey = sprintf(self::LEVELDB_USER_VARIABLE_KEY, $userId, $key) => json_encode(
                    array(
                        'expires' => $ttl ? (time() + $ttl) : 0,
                        'data'    => $data,
                    )
                ),
            ));

            $LevelDb->execute();
            if ($Request->getResult() === true){
                return true;
            }
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