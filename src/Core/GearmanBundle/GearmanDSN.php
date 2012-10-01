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
        DEFAULT_PORT = 6379,

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
     * Gearman host setter
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
     * Gearman port getter
     *
     * @return int
     */
    public function getPort() {
        return $this->port;
    }

    /**
     * Gearman port setter
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
     * Gearman client timeout getter
     *
     * @return float
     */
    public function getTimeout() {
        return $this->timeout;
    }

    /**
     * Redis timeout setter
     *
     * @param float $timeout
     * @return RedisDSN
     * @throws RedisDSNException
     */
    public function setTimeout($timeout) {
        if (is_float($timeout) || is_double($timeout)) {
            $this->timeout = $timeout;
            return $this;
        }

        throw new RedisDSNException("Invalid timeout: " . var_export($timeout, true));
    }

    /**
     * Redis persistent getter
     *
     * @return bool
     */
    public function getPersistent() {
        return $this->persistent;
    }

    /**
     * Redis persistent setter
     *
     * @param bool $persistent
     * @return RedisDSN
     * @throws RedisDSNException
     */
    public function setPersistent($persistent) {
        if (is_bool($persistent)) {
            $this->persistent = $persistent;
            return $this;
        }

        throw new RedisDSNException("Invalid persistent: " . var_export($persistent, true));
    }

    /**
     * Return string DSN
     *
     * @return string
     */
    public function __toString() {
        return "redis://" . $this->getHost() . ":" . $this->getPort() . "/" . $this->getDatabase() . "?timeout=" . $this->getTimeout() . "&persistent=" . ($this->getPersistent() ? "true" : "false") . "&prefix=" . $this->getPrefix();
    }

    /**
     * Returns RedisDSN from array
     *
     * @static
     * @return RedisDSN
     */
    public static function getDSNFromArray(array $dsn) {
        $result = new RedisDSN;

        if (isset($dsn['host'])) {
            $result->setHost($dsn['host']);
        } else {
            throw new RedisDSNException("Invalid host");
        }

        if (isset($dsn['port'])) {
            $result->setPort($dsn['port']);
        }

        if (isset($dsn['timeout'])) {
            $result->setTimeout($dsn['timeout']);
        }

        if (isset($dsn['options']) && is_array($dsn['options'])) {
            if (isset($dsn['options']['persistent'])) {
                $result->setPersistent($dsn['options']['persistent']);
            }

            if (isset($dsn['options']['database'])) {
                $result->setDatabase($dsn['options']['database']);
            }

            if (isset($dsn['options']['prefix'])) {
                $result->setPrefix($dsn['options']['prefix']);
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
            $result = new RedisDSN;
            $result->setHost($dsn['host']);

            if (isset($dsn['port'])) {
                $result->setPort($dsn['port']);
            }

            if (isset($dsn['path'])) {
                $path = (int) trim($dsn['path'], DIRECTORY_SEPARATOR);
                $result->setDatabase($path);
            }

            if (isset($dsn['query'])) {
                parse_str($dsn['query'], $query);

                if (isset($query['timeout'])) {
                    $timeout = (float) $query['timeout'];
                    if ($timeout) {
                        $result->setTimeout($timeout);
                    } else {
                        throw new RedisDSNException("Invalid timeout: " . var_export($timeout, true));
                    }
                }

                if (isset($query['persistent'])) {
                    $persistent = $query['persistent'];
                    if (strtolower($persistent) == 'true') {
                        $persistent = true;
                    } elseif (strtolower($persistent) == 'false') {
                        $persistent = false;
                    } else {
                        throw new RedisDSNException("Invalid persistent: " . var_export($persistent, true));
                    }

                    $result->setPersistent($persistent);
                }

                if (isset($query['prefix'])) {
                    $result->setPrefix($query['prefix']);
                }
            }

            return $result;
        }

        throw new RedisDSNException("Could not parse dsn string");
    }
}

/**
 * GearmanDSNException
 *
 * @package GearmanBundle
 */
class GearmanDSNException extends \Exception {

}