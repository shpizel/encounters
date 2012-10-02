<?php
namespace Core\GearmanBundle;

/**
 * GearmanDSN
 *
 * @package GearmanBundle
 */
class GearmanDSN {

    const

        /**
         * Default gearman port
         *
         * @var int
         */
        DEFAULT_PORT = 4730,

        /**
         * Default client timeout
         *
         * @var int
         */
        DEFAULT_CLIENT_TIMEOUT = 1000,

        /**
         * Default worker timeout
         *
         * @var int
         */
        DEFAULT_WORKER_TIMEOUT = 5000
    ;

    private

        /**
         * Host
         *
         * @var string
         */
        $host,

        /**
         * Port
         *
         * @var int
         */
        $port = self::DEFAULT_PORT,

        /**
         * Client timeout
         *
         * @var float
         */
        $clientTimeout = self::DEFAULT_CLIENT_TIMEOUT,

        /**
         * Worker timeout
         *
         * @var float
         */
        $workerTimeout = self::DEFAULT_WORKER_TIMEOUT
    ;

    /**
     * Gearman host getter
     *
     * @return string
     */
    public function getHost() {
        return $this->host;
    }

    /**
     * GearmanDSN host setter
     *
     * @param string $host
     * @return GearmanDSN
     */
    public function setHost($host) {
        if (is_string($host)) {
            $this->host = $host;
            return $this;
        }

        throw new GearmanDSNException("Invalid host: " . var_export($host, true));
    }

    /**
     * GearmanDSN port getter
     *
     * @return int
     */
    public function getPort() {
        return $this->port;
    }

    /**
     * GearmanDSN port setter
     *
     * @param int $port
     * @return GearmanDSN
     * @throws GearmanDSNException
     */
    public function setPort($port) {
        if (is_int($port)) {
            $this->port = $port;
            return $this;
        }

        throw new GearmanDSNException("Invalid port: " . var_export($port, true));
    }

    /**
     * GearmanClient timeout getter
     *
     * @return int
     */
    public function getClientTimeout() {
        return $this->clientTimeout;
    }

    /**
     * GearmanClient timeout setter
     *
     * @param int $timeout
     * @return GearmanDSN
     * @throws GearmanDSNException
     */
    public function setClientTimeout($timeout) {
        if (is_int($timeout)) {
            $this->clientTimeout = $timeout;
            return $this;
        }

        throw new GearmanDSNException("Invalid timeout: " . var_export($timeout, true));
    }

    /**
     * GearmanWorker timeout getter
     *
     * @return int
     */
    public function getWorkerTimeout() {
        return $this->workerTimeout;
    }

    /**
     * GearmanWorker timeout setter
     *
     * @param int $timeout
     * @return GearmanDSN
     * @throws GearmanDSNException
     */
    public function setWorkerTimeout($timeout) {
        if (is_int($timeout)) {
            $this->workerTimeout = $timeout;
            return $this;
        }

        throw new GearmanDSNException("Invalid timeout: " . var_export($timeout, true));
    }

    /**
     * Return string DSN
     *
     * @return string
     */
    public function __toString() {
        return "gearman://" . $this->getHost() . ":" . $this->getPort() . "?client_timeout=" . $this->getClientTimeout() . "&worker_timeout=" . $this->getWorkerTimeout();
    }

    /**
     * Returns GearmanDSN from array
     *
     * @static
     * @return GearmanDSN
     */
    public static function getDSNFromArray(array $dsn) {
        $result = new GearmanDSN;

        if (isset($dsn['host'])) {
            $result->setHost($dsn['host']);
        } else {
            throw new GearmanDSNException("Invalid host");
        }

        if (isset($dsn['port'])) {
            $result->setPort($dsn['port']);
        }

        if (isset($dsn['options']) && is_array($dsn['options'])) {
            if (isset($dsn['options']['client_timeout'])) {
                $result->setClientTimeout($dsn['options']['client_timeout']);
            }

            if (isset($dsn['options']['worker_timeout'])) {
                $result->setWorkerTimeout($dsn['options']['worker_timeout']);
            }
        }

        return $result;
    }

    /**
     * Returns RedisDSN from string
     *
     * @static
     * @return RedisDSN
     */
    public static function getDSNFromString($dsn) {
        if ($dsn = parse_url($dsn)) {
            $result = new GearmanDSN;
            $result->setHost($dsn['host']);

            if (isset($dsn['port'])) {
                $result->setPort($dsn['port']);
            }

            if (isset($dsn['query'])) {
                parse_str($dsn['query'], $query);

                if (isset($query['client_timeout'])) {
                    $timeout = (int) $query['client_timeout'];
                    if ($timeout) {
                        $result->setClientTimeout($timeout);
                    } else {
                        throw new GearmanDSNException("Invalid timeout: " . var_export($timeout, true));
                    }
                }

                if (isset($query['worker_timeout'])) {
                    $timeout = (int) $query['worker_timeout'];
                    if ($timeout) {
                        $result->setWorkerTimeout($timeout);
                    } else {
                        throw new GearmanDSNException("Invalid timeout: " . var_export($timeout, true));
                    }
                }
            }

            return $result;
        }

        throw new GearmanDSNException("Could not parse dsn string");
    }
}

/**
 * GearmanDSNException
 *
 * @package GearmanBundle
 */
class GearmanDSNException extends \Exception {

}