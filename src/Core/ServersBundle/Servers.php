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
        $servers = array(
            'www'     => array(),
            'memory'  => array(),
            'storage' => array(),
            'script'  => array(),
        )
    ;

    /**
     * Конструктор
     *
     * @param $servers
     */
    public function __construct($wwwServers, $memoryServers, $storageServers, $scriptServers) {
        foreach ($wwwServers as $server) {
            $name = $server['name'];
            $host = $server['host'];

            $this->servers['www'][$name] = $host;
        }

        foreach ($memoryServers as $server) {
            $name = $server['name'];
            $host = $server['host'];

            $this->servers['memory'][$name] = $host;
        }

        foreach ($storageServers as $server) {
            $name = $server['name'];
            $host = $server['host'];

            $this->servers['storage'][$name] = $host;
        }

        foreach ($scriptServers as $server) {
            $name = $server['name'];
            $host = $server['host'];

            $this->servers['script'][$name] = $host;
        }
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
     * @param str $serverType = {www, memory, storage, script}
     * @return array
     */
    public function getServers($serverType = null) {
        return ($serverType && isset($this->servers[$serverType])) ? $this->servers[$serverType] : $this->servers;
    }
}

/**
 * ServersException
 *
 * @package ServersBundle
 */
class ServersException extends \Exception {

}