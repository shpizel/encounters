<?php
namespace Mamba\GearmanBundle;

/**
 * Gearman
 *
 * @package GermanBundle
 */
class Gearman {

    const

        /**
         * Дефолтный таймаут для клиента
         *
         * @var int
         */
        GEARMAN_CLIENT_TIMEOUT_DEFAULT = 1000,

        /**
         * Дефолтный таймаут для воркера
         *
         * @var int
         */
        GEARMAN_WORKER_TIMEOUT_DEFAULT = 5000
    ;

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
        $Client->setTimeout(self::GEARMAN_CLIENT_TIMEOUT_DEFAULT);

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
        $Worker->setTimeout(self::GEARMAN_WORKER_TIMEOUT_DEFAULT);

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