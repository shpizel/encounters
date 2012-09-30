<?php
namespace Core\ServersBundle;

/**
 * Servers
 *
 * @package ServersBundle
 */
class Servers {

    private

        /**
         * Servers
         *
         * @var array
         */
        $servers = array()
    ;

    /**
     * Конструктор
     *
     * @param $servers
     */
    public function __construct($wwwServers, $memoryServers, $storageServers, $scriptServers) {
        $this->servers = array(
            'www'     => $wwwServers,
            'memory'  => $memoryServers,
            'storage' => $storageServers,
            'script'  => $scriptServers,
        );
    }

    /**
     * WWW servers getter
     *
     * @return array
     */
    public function getWWWServers() {
        return $this->servers['www'];
    }

    /**
     * Memory servers getter
     *
     * @return array
     */
    public function getMemoryServers() {
        return $this->servers['memory'];
    }

    /**
     * Storage servers getter
     *
     * @return array
     */
    public function getStorageServers() {
        return $this->servers['storage'];
    }

    /**
     * Script servers getter
     *
     * @return array
     */
    public function getScriptServers() {
        return $this->servers['script'];
    }

    /**
     * Servers getter
     *
     * @return array
     */
    public function getServers($serverType) {
        return (isset($this->servers[$serverType])) ? $this->servers[$serverType] : array();
    }
}

/**
 * ServersException
 *
 * @package ServersBundle
 */
class ServersException extends \Exception {

}