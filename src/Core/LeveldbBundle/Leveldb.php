<?php

namespace Core\LeveldbBundle;

/**
 * Class Leveldb
 *
 * @package Core\LeveldbBundle
 */
class Leveldb {

    private

        /**
         * Master
         *
         * @var array
         */
        $master = array(),

        /**
         * Slave
         *
         * @var array
         */
        $slave = array(),

        /**
         * Queue
         *
         * @var array
         */
        $queue = array(),

        /**
         * Request id
         *
         * @var int
         */
        $id = 0
    ;

    /**
     * Constructor
     *
     * @param array $master
     * @param array $slave
     */
    public function __construct(array $master, array $slave) {
        $this->master = array_shift($master);
        $this->slave  = array_shift($slave);
    }

    /**
     * Request unique id getter
     *
     * @return int
     */
    private function getRequestId() {
        return ++$this->id;
    }

    /**
     * Master connection getter
     *
     * @return resource
     * @throws LeveldbException
     */
    private function getMasterConnection() {
        if (isset($this->master['connection']) && $this->master['connection']) {
            return $this->master['connection'];
        }

        $errorNumber = $errorString = null;
        if ($masterConnection = fsockopen($this->master['host'], (int) $this->master['port'], $errorNumber, $errorString, $this->master['timeout'])) {
            return $this->master['connection'] = $masterConnection;
        } else {
            throw new LeveldbException("Could not connect to master {$this->master['host']}:{$this->master['port']}, '{$errorString}'", $errorNumber);
        }
    }

    private function getSlaveConnection() {
        if (isset($this->slave['connection']) && $this->slave['connection']) {
            return $this->slave['connection'];
        }

        $errorNumber = $errorString = null;
        if ($slaveConnection = fsockopen($this->slave['host'], (int) $this->slave['port'], $errorNumber, $errorString, $this->slave['timeout'])) {
            return $this->slave['connection'] = $slaveConnection;
        } else {
            throw new LeveldbException("Could not connect to slave {$this->slave['host']}:{$this->slave['port']}, '{$errorString}'", $errorNumber);
        }
    }

    /**
     * Request sender
     *
     * @param $connection
     * @param LeveldbRequest $Request
     * @return $this
     * @throws LeveldbException
     */
    private function sendRequest($connection, $Request) {
        $data = array(
            'jsonrpc' => '2.0',
            'method'  => $Request->getMethod(),
            'params'  => $Request->getData(),
            'id'      => $Request->getId(),
        );

        if (!fwrite($connection, json_encode($data) . "\r\n")) {
            throw new LeveldbException("fwrite failed");
        }

        return $this;
    }

    private function isMasterRequest($method) {
        if (in_array($method, array('set', 'del', 'inc', 'inc_add', 'update_packed', 'rep_status'))) {
            return true;
        }

        return false;
    }

    private function isSlaveRequest($method) {
        if (in_array($method, array('get', 'get_range'))) {
            return true;
        }

        return false;
    }

    public function execute() {
        $masterQueue = $slaveQueue = array();

        while($Request = array_shift($this->queue)) {
            $method = $Request->getMethod();

            if ($this->isMasterRequest($method)) {
                $masterQueue[$Request->getId()] = $Request;
            } elseif ($this->isSlaveRequest($method)) {
                $slaveQueue[$Request->getId()] = $Request;
            } else {
                throw new LeveldbException("Invalid method: {$method}");
            }
        }

        if (($masterQueue && !$this->getMasterConnection()) || ($slaveQueue && !$this->getSlaveConnection())) {
            return $this;
        }

        while (count($masterQueue) > 0) {
            if (!($data = trim(fgets($this->getMasterConnection())))) {
                throw new LeveldbException("No data recieved");
            }

            $data = json_decode($data, true);

            if (isset($data['error']) ) {
                throw new LeveldbException(var_export($data['error'], true));
            } elseif (!isset($data['id'])) {
                throw new LeveldbException("Id not found");
            } elseif (!isset($masterQueue[$data['id']])) {
                throw new LeveldbException("Invalid data recieved");
            }

            $masterQueue[$data['id']]->setResult($data['result']);
            unset($masterQueue[$data['id']]);
        }

        while (count($slaveQueue) > 0) {
            if (!($data = trim(fgets($this->getSlaveConnection())))) {
                throw new LeveldbException("No data recieved");
            }

            $data = json_decode($data, true);

            if (isset($data['error']) ) {
                throw new LeveldbException(var_export($data['error'], true));
            } elseif (!isset($data['id'])) {
                throw new LeveldbException("Id not found");
            } elseif (!isset($slaveQueue[$data['id']])) {
                throw new LeveldbException("Invalid data recieved");
            }

            $slaveQueue[$data['id']]->setResult($data['result']);
            unset($slaveQueue[$data['id']]);
        }

        return $this;
    }

    /**
     * Leveldb getter
     *
     * @param array|string $keys
     * @return LeveldbRequest
     */
    public function get($keys) {
        if (is_string($keys)) {
            $keys = array($keys);
        }

        $Request = new LeveldbRequest();
        $Request
            ->setMethod(__FUNCTION__)
            ->setData($keys)
            ->setId($requestId = $this->getRequestId())
        ;

        $this->queue[$requestId] = $Request;

        $this->sendRequest($this->getSlaveConnection(), $Request);
        return $Request;
    }

    /**
     * Leveldb replication status getter
     *
     * @return LeveldbRequest
     */
    public function rep_status() {
        $Request = new LeveldbRequest();
        $Request
            ->setMethod(__FUNCTION__)
            ->setData(array())
            ->setId($requestId = $this->getRequestId())
        ;

        $this->queue[$requestId] = $Request;

        $this->sendRequest($this->getMasterConnection(), $Request);
        return $Request;
    }

    /**
     * Leveldb get_range
     *
     * @param string $from ключ или префикс ключа, с которого начинается диапазон
     * @param string|null $to ключ или префикс ключа, которым заканчивается диапазон. Если пуст, значит выборка продолжается до конца базы
     * @param int $limit количество ключей для выборки
     * @param int $skip Количество ключей, которые необходимо пропустить от начала выборки.
     * @return LeveldbRequest
     */
    public function get_range($from, $to = null, $limit = 100, $skip = 0) {
        $Request = new LeveldbRequest();
        $Request
            ->setMethod(__FUNCTION__)
            ->setData(array(
                'from'  => $from,
                'to'    => $to,
                'limit' => $limit,
                'skip'  => $skip,
            ))
            ->setId($requestId = $this->getRequestId())
        ;

        $this->queue[$requestId] = $Request;

        $this->sendRequest($this->getSlaveConnection(), $Request);
        return $Request;
    }

    /**
     * Leveldb setter
     *
     * @param array $data
     * @param bool $return
     * @return LeveldbRequest
     */
    public function set(array $data) {
        $Request = new LeveldbRequest();
        $Request
            ->setMethod(__FUNCTION__)
            ->setData($data)
            ->setId($requestId = $this->getRequestId())
        ;

        $this->queue[$requestId] = $Request;

        $this->sendRequest($this->getMasterConnection(), $Request);
        return $Request;
    }

    /**
     * Leveldb deleter
     *
     * @param array $data
     * @param bool $return
     * @return LeveldbRequest
     */
    public function del(array $data) {
        $Request = new LeveldbRequest();
        $Request
            ->setMethod(__FUNCTION__)
            ->setData($data)
            ->setId($requestId = $this->getRequestId())
        ;

        $this->queue[$requestId] = $Request;

        $this->sendRequest($this->getMasterConnection(), $Request);
        return $Request;
    }

    /**
     * Leveldb incrementer
     *
     * @param array $data
     * @return LeveldbRequest
     */
    public function inc(array $data) {
        $Request = new LeveldbRequest();
        $Request
            ->setMethod(__FUNCTION__)
            ->setData($data)
            ->setId($requestId = $this->getRequestId())
        ;

        $this->queue[$requestId] = $Request;

        $this->sendRequest($this->getMasterConnection(), $Request);
        return $Request;
    }

    /**
     * Leveldb incrementer with add
     *
     * @param array $incrementList
     * @param array $defaultList
     * @return LeveldbRequest
     */
    public function inc_add(array $incrementList, array $defaultList) {
        $Request = new LeveldbRequest();
        $Request
            ->setMethod(__FUNCTION__)
            ->setData(array(
                'inc' => $incrementList,
                'def' => $defaultList,
            ))
            ->setId($requestId = $this->getRequestId())
        ;

        $this->queue[$requestId] = $Request;

        $this->sendRequest($this->getMasterConnection(), $Request);
        return $Request;
    }

    /**
     * Leveldb update_packed
     *
     * @param $key ключ обновляемой структуры
     * @param array $incrementList ассоциативный массив ключ => целое значение для инкремента полей структуры
     * @param array $setList ассоциативный массив ключ => значение для установки полей структуры
     * @param array $defaultList ассоциативный массив ключ => целое значение. Эти значения используются в качестве базовых для операции инкремента полей.
     * @return LeveldbRequest
     */
    public function update_packed(
        $key,
        array $incrementList = array(),
        array $setList = array(),
        array $defaultList = array()
    ) {
        $Request = new LeveldbRequest();
        $Request
            ->setMethod(__FUNCTION__)
            ->setData(array(
                'key' => $key,
                'inc' => $incrementList,
                'set' => $setList,
                'def' => $defaultList,
            ))
            ->setId($requestId = $this->getRequestId())
        ;

        $this->queue[$requestId] = $Request;

        $this->sendRequest($this->getMasterConnection(), $Request);
        return $Request;
    }
}

/**
 * Class LeveldbException
 *
 * @package Core\LeveldbBundle
 */
class LeveldbException extends \Exception {

}