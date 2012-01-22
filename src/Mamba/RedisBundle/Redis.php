<?php

namespace Mamba\RedisBundle;

class Redis extends \Redis {

    const

        USE_PERMANENT_CONNECTION = false
    ;

    public function __construct($host, $port, $timeout) {
        parent::__construct();
        if (self::USE_PERMANENT_CONNECTION) {
            if (!$timeout) {
                $this->pconnect($host, $port);
            } else {
                $this->pconnect($host, $port, $timeout);
            }
        } else {
            if (!$timeout) {
                $this->connect($host, $port);
            } else {
                $this->connect($host, $port, $timeout);
            }
        }

        $this->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_IGBINARY);
    }
}

class RedisException extends \Exception {

}