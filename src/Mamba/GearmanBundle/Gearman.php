<?php
namespace Mamba\GearmanBundle;

/**
 * Gearman
 *
 * @package GermanBundle
 */
class Gearman {

    protected static

        /**
         * Worker Instance
         *
         * @var GearmanWorker
         */
        $Worker,

        /**
         * Client Instance
         *
         * @var GearmanClient
         */
        $Client,

        /**
         * Серверы
         *
         * @var array
         */
        $servers = array(

        )
    ;

    /**
     * Конструктор
     *
     * @param $servers
     */
    public function __construct($servers) {
        self::$servers = $servers;
    }

    /**
     * Gearman Client Instance Getter
     *
     * @static
     * @return \GearmanClient
     */
    public static function getClient() {
        if (self::$Client) {
            return self::$Client;
        }

        $Client = new \GearmanClient();
        foreach (self::$servers as $server) {
            $Client->addServer($server['host'], $server['port']);
        }

        return self::$Client = $Client;
    }

    /**
     * Gearman Worker Instance Getter
     *
     * @static
     * @return \GearmanWorker
     */
    public static function getWorker() {
        if (self::$Worker) {
            return self::$Worker;
        }

        $Worker = new \GearmanWorker();
        foreach (self::$servers as $server) {
            $Worker->addServer($server['host'], $server['port']);
        }

        return self::$Worker = $Worker;
    }
}

/**
 * GearmanException
 *
 * @package GearmanBundle
 */
class GearmanException extends \Exception {

}