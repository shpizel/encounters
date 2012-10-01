<?php
namespace Core\GearmanBundle;

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
        GEARMAN_WORKER_TIMEOUT_DEFAULT = 5000,

        /**
         * Client const
         *
         * @var int
         */
        GEARMAN_CLIENT = 1,

        /**
         * Worker constant
         *
         * @var int
         */
        GEARMAN_WORKER = 2
    ;

    private

        /**
         * Ноды германа
         *
         * @var array
         */
        $nodes = array(),

        /**
         * Пул соединений
         *
         * @var array
         */
        $connections = array()
    ;

    /**
     * Конструктор
     *
     * @param array $nodes
     */
    public function __construct(array $nodes) {
        $this->nodes = $nodes;
    }

    /**
     * Nodes getter
     *
     * @return array
     */
    public function getNodes() {
        return $this->nodes;
    }

    /**
     * Node connection getter
     *
     * @return \GearmanClient | \GearmanWorker
     */
    public function getNodeConnection(array $node, $type) {
        $dsn = "gearman://{$node['host']}:{$node['port']}";
        if ($type == self::GEARMAN_CLIENT || $type == 'client') {
            $dsn .= "/client";

            if (isset($this->connections[$dsn])) {
                return $this->connections[$dsn];
            }

            $Client = new \GearmanClient();
            $Client->addServer($node['host'], $node['port']);
            $Client->setTimeout(self::GEARMAN_CLIENT_TIMEOUT_DEFAULT);

            return $this->connections[$dsn] = $Client;
        } elseif ($type == self::GEARMAN_WORKER || $type == 'worker') {
            $dsn .= "/worker";

            if (isset($this->connections[$dsn])) {
                return $this->connections[$dsn];
            }

            $Worker = new \GearmanWorker();
            $Worker->addServer($node['host'], $node['port']);
            $Worker->setTimeout(self::GEARMAN_WORKER_TIMEOUT_DEFAULT);

            return $this->connections[$dsn] = $Worker;
        }

        throw new GearmanException("Invalid type");
    }

    /**
     * Returns node number by key
     *
     * @param string $key
     * @return int
     */
    public function getNodeNumberByKey($key) {
        return crc32($key) % count($this->nodes);
    }

    /**
     * Node connection getter by number
     *
     * @param int $number
     * @param mixed $type
     * @return \GearmanClient|\GearmanWorker
     */
    public function getNodeConnectionByNodeNumber($number, $type) {
        return $this->getNodeConnection($this->nodes[$number], $type);
    }


}

/**
 * GearmanException
 *
 * @package GearmanBundle
 */
class GearmanException extends \Exception {

}