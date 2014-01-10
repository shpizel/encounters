<?php
namespace Core\MySQLBundle;

use PDO;
use Core\MySQLBundle\MySQL;
use Doctrine\DBAL\Statement;

/**
 * Class Query
 * @package Core\MySQLBundle
 */
class Query {

    private

        /**
         * @var \Doctrine\DBAL\Statement
         */
        $Statement,

        /**
         * @var array
         */
        $args = [],

        /**
         * @var bool|null
         */
        $result = null
    ;

    /**
     * Constructor
     *
     * @param str $sql
     */
    public function __construct($sql) {
        $this->Statement = MySQL::getInstance()->getConnection()->prepare($this->sql = $sql);
    }

    /**
     * Binder
     *
     * @param $paramName
     * @param $paramValue
     * @param $paramType
     */
    public function bind($paramName, $paramValue, $paramType = null) {
        $this->result = null;

        if ($this->Statement->bindValue($paramName, $paramValue, $paramType)) {
            $this->args[$paramName] = $paramValue;

            return $this;
        }

        throw new QueryException("Invalid bind");
    }

    public function bindArray($bindArray) {
        foreach ($bindArray as $item) {
            if (count($item) == 3) {
                list($paramName, $paramValue, $paramType) = $item;
            } elseif (count($item) == 2) {
                list($paramName, $paramValue) = $item;
                $paramType = null;
            } else {
                throw new QueryException("Invalid bindArray item");
            }

            $this->bind($paramName, $paramValue, $paramType);
        }

        return $this;
    }

    /**
     * Query executor
     *
     * @return $this
     */
    public function execute() {
        $startTime = microtime(true);

        $this->result = $this->Statement->execute();

        $MySQL = MySQL::getInstance();
        if ($MySQL->metricsEnabled) {
            $MySQL->metrics['requests'][] = array(
                'method'  => $this->sql,
                'args'    => $this->args,
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $MySQL->metrics['timeout']+=$timeout;
        }

        return $this;
    }

    /**
     * Fetcher
     *
     * @param int $mode
     * @return mixed
     * @throws QueryException
     */
    public function fetch($mode = PDO::FETCH_ASSOC) {
        if ($this->result) {
            return $this->Statement->fetch($mode);
        }

        throw new QueryException("Fetch with no result");
    }

    /**
     * Result getter
     *
     * @return bool|null
     */
    public function getResult() {
        return $this->result;
    }

    /**
     * Statement getter
     *
     * @return Statement
     */
    public function getStatement() {
        return $this->Statement;
    }
}

/**
 * Class QueryException
 * @package Core\MySQLBundle
 */
class QueryException extends \Exception {

}