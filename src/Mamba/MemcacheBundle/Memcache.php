<?php

namespace Mamba\MemcacheBundle;

class Memcache extends \Memcached {

    public function __construct($servers) {
        parent::__construct();

        foreach ($servers as $server) {
            if (!isset($server['host'])) {
                throw new MemcacheException("Memcached host must be defined for server $server");
            }

            if (!isset($server['port'])) {
                throw new MemcacheException("Memcached port must be defined for server $server");
            }

            if (!isset($server['weight'])) {
                $server['weight'] = 0;
            }

            $this->addServer($server['host'], $server['port'], $server['weight']);

        }
    }
}

class MemcacheException extends \Exception {

}