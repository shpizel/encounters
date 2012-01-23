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

    public function __construct($host, $port, $timeout) {
        parent::__construct();

        $connect = self::USE_PERSISTENT_CONNECTION ? "pconnect" : "connect";
        $this->$connect($host, $port, $timeout);

        $this->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_IGBINARY);
    }
}

class RedisException extends \Exception {

}