<?php
namespace Core\GearmanBundle;

/**
 * Gearman
 *
 * @package GermanBundle
 */
class Gearman {

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
        $this->nodes = array_map(function($nodeArray) {
            return GearmanDSN::getDSNFromArray($nodeArray);
        }, $nodes);
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
     * Gearman client getter
     *
     * @param GearmanDSN $node (if $node is NULL -> we will connect to random instance)
     * @return \GearmanClient
     */
    public function getClient(GearmanDSN $node = null) {
        if (null === $node) {
            $node = $this->nodes[array_rand($this->nodes)];
        }

        $dsn = (string) $node . "/client";

        if (isset($this->connections[$dsn])) {
            return $this->connections[$dsn];
        }

        $Client = new \GearmanClient();
        $Client->addServer($node->getHost(), $node->getPort());
        $Client->setTimeout($node->getClientTimeout());

        return $this->connections[$dsn] = $Client;
    }

    /**
     * Gearman worker getter
     *
     * @param GearmanDSN $node (if $node is NULL -> we will connect to random instance)
     * @return \GearmanWorker
     */
    public function getWorker(GearmanDSN $node = null) {
        if (null === $node) {
            $node = $this->nodes[array_rand($this->nodes)];
        }

        $dsn = (string) $node . "/worker";

        if (isset($this->connections[$dsn])) {
            return $this->connections[$dsn];
        }

        $Worker = new \GearmanWorker();
        $Worker->addServer($node->getHost(), $node->getPort());
        $Worker->setTimeout($node->getWorkerTimeout());

        return $this->connections[$dsn] = $Worker;
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
}

/**
 * GearmanException
 *
 * @package GearmanBundle
 */
class GearmanException extends \Exception {

}