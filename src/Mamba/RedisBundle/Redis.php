<?php
namespace Mamba\RedisBundle;

/**
 * Redis
 *
 * @package RedisBundle
 */
class Redis extends \Redis {

    const

        /**
         * Использовать ли персистеные соединения
         *
         * @var bool
         */
        USE_PERSISTENT_CONNECTION = false
    ;

    public function __construct($host, $port, $timeout, $database) {
        parent::__construct();

        $connect = self::USE_PERSISTENT_CONNECTION ? "pconnect" : "connect";
        $this->$connect($host, $port, $timeout);

        $database && $this->select($database);
    }
}

/**
 * RedisException
 *
 * @package RedisBundle
 */
class RedisException extends \Exception {

}