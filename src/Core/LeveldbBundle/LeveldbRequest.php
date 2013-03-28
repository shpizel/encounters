<?php

namespace Core\LeveldbBundle;

/**
 * Class LeveldbRequest
 *
 * @package Core\LeveldbBundle
 */
class LeveldbRequest {

    private

        /**
         * Method
         *
         * @var string
         */
        $method,

        /**
         * Data
         *
         * @var array
         */
        $data,

        /**
         * Request id
         *
         * @var int
         */
        $id
    ;

    /**
     * Request method setter
     *
     * @param $method
     * @return $this
     */
    public function setMethod($method) {
        $this->method = $method;

        return $this;
    }

    /**
     * Method getter
     *
     * @return mixed
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * Id setter
     *
     * @param int $id
     * @return $this
     */
    public function setId($id) {
        $this->id = $id;

        return $this;
    }

    /**
     * Id getter
     *
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Data setter
     *
     * @param array $data
     * @return $this
     */
    public function setData(array $data) {
        $this->data = $data;

        return $this;
    }

    /**
     * Data getter
     *
     * @return mixed
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Result setter
     *
     * @param $result
     * @return $this
     */
    public function setResult($result) {
        $this->result = $result;

        return $this;
    }

    /**
     * Result getter
     *
     * @return mixed
     */
    public function getResult() {
        return $this->result;
    }
}

/**
 * Class LeveldbRequestException
 *
 * @package Core\LeveldbBundle
 */
class LeveldbRequestException extends \Exception {

}