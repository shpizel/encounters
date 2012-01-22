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
        $Client
    ;

    /**
     * Конструктор
     *
     * @param $servers
     */
    public function __construct() {

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
        $Client->addServer();
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
        $Worker->addServer();
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