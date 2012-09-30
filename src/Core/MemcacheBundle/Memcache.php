<?php
namespace Core\MemcacheBundle;

/**
 * Memcache
 *
 * @package MemcacheBundle
 */
class Memcache extends \Memcached {

    /**
     * Конструктор
     *
     * @param $servers
     */
    public function __construct($servers) {
        parent::__construct();

        foreach ($servers as $server) {
            $this->addServer(
                $server['host'],
                $server['port'],
                $server['weight']
            );
        }
    }
}

/**
 * MemcacheException
 *
 * @package MemcacheBundle
 */
class MemcacheException extends \Exception {

}