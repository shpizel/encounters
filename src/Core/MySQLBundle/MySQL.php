<?php
namespace Core\MySQLBundle;

use Symfony\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;

use Core\MemcacheBundle\Memcache;
use Core\RedisBundle\Redis;
use Core\LeveldbBundle\Leveldb;

class MySQL {

    private

        /**
         * Doctrine
         *
         * @var Registry
         */
         $Doctrine,

        /**
         * @var Memcache
         */
        $Memcache,

        /**
         * @var Redis
         */
        $Redis,

        /**
         * @var Leveldb
         */
        $Leveldb,

        /**
         * @var Connection
         */
        $Connection,

        /**
         * @var array
         */
        $Queries = []
    ;

    private static

        /**
         * @var MySQL
         */
        $Instance
    ;

    public

        /**
         * Метрики использования
         *
         * @var array
         */
        $metrics = array(
            'requests' => array(),
            'timeout'  => 0,
        ),

        /**
         * Метрики использования включены?
         *
         * @var bool
         */
        $metricsEnabled = true
    ;

    /**
     * @param $Doctrine
     * @param $Memcache
     * @param $Redis
     * @param $LevelDB
     */
    public function __construct($Doctrine, $Memcache, $Redis, $Leveldb) {
        list($this->Doctrine, $this->Memcache, $this->Redis, $this->Leveldb) = array(
            $Doctrine,
            $Memcache,
            $Redis,
            $Leveldb,
        );

        self::$Instance = $this;
    }

    /**
     * Instance getter
     *
     * @return MySQL
     */
    public static function getInstance() {
        return self::$Instance;
    }

    /**
     * Connection getter
     *
     * @return Connection
     */
    public function getConnection() {
        if ($this->Connection) {
            return $this->Connection;
        }

        return $this->Connection = $this->Doctrine->getConnection();
    }

    /**
     * Returns usage metrics
     *
     * @return array
     */
    public function getMetrics() {
        return $this->metrics;
    }

    /**
     * Metrics enabler
     *
     * @param bool $enabled
     * @throws MySQLException
     */
    public function setMetricsEnabled($enabled) {
        if (!is_bool($enabled)) {
            throw new MySQLException("Invalid param");
        }

        $this->metricsEnabled = $enabled;
        return $this;
    }

    public function getQuery($sql) {
        $queryHash = crypt($sql);

        if (isset($this->Queries[$queryHash])) {
            return $this->Queries[$queryHash];
        }

        return
            $this->Queries[$queryHash] = new Query($sql);
        ;
    }

    public function exec($sql) {
        return $this->getConnection()->exec($sql);
    }

    public function getLastInsertId() {
        $this->Connection->lastInsertId();
    }

    public function quote($input, $type = null) {
        return $this->getConnection()->quote($input, $type);
    }
}

/**
 * Class MySQLException
 * @package Core\MySQLBundle
 */
class MySQLException extends \Exception {

}