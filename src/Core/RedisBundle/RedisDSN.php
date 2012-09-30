<?php
namespace Core\RedisBundle;

/**
 * RedisDSN
 *
 * @package RedisBundle
 */
class RedisDSN {

    const

        /**
         * Default redis port
         *
         * @var int
         */
        DEFAULT_PORT = 6379,

        /**
         * Default connection timeout
         *
         * @var float
         */
        DEFAULT_TIMEOUT = 2.5,

        /**
         * Default persistent
         *
         * @var bool
         */
        DEFAULT_PERSISTENT = true,

        /**
         * Default database
         *
         * @var int
         */
        DEFAULT_DATABASE = 0,

        /**
         * Default prefix
         *
         * @var string
         */
        DEFAULT_PREFIX = null
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
         * Connection timeout
         *
         * @var float
         */
        $timeout = self::DEFAULT_TIMEOUT,

        /**
         * Persistent
         *
         * @var bool
         */
        $persistent = self::DEFAULT_PERSISTENT,

        /**
         * Database
         *
         * @var int
         */
        $database = self::DEFAULT_DATABASE,

        /**
         * Prefix
         *
         * @var string
         */
        $prefix = self::DEFAULT_PREFIX
    ;

    /**
     * Redis host getter
     *
     * @return string
     */
    public function getHost() {
        return $this->host;
    }

    /**
     * Redis host setter
     *
     * @param string $host
     * @return RedisDSN
     */
    public function setHost($host) {
        if (is_string($host)) {
            $this->host = $host;
            return $this;
        }

        throw new RedisDSNException("Invalid host: " . var_export($host, true));
    }

    /**
     * Redis port getter
     *
     * @return int
     */
    public function getPort() {
        return $this->port;
    }

    /**
     * Redis port setter
     *
     * @param int $port
     * @return RedisDSN
     * @throws RedisDSNException
     */
    public function setPort($port) {
        if (is_int($port)) {
            $this->port = $port;
            return $this;
        }

        throw new RedisDSNException("Invalid port: " . var_export($port, true));
    }

    /**
     * Redis database getter
     *
     * @return int
     */
    public function getDatabase() {
        return $this->database;
    }

    /**
     * Redis database setter
     *
     * @param int $database
     * @return RedisDSN
     * @throws RedisDSNException
     */
    public function setDatabase($database) {
        if (is_int($database)) {
            $this->database = $database;
            return $this;
        }

        throw new RedisDSNException("Invalid database: " . var_export($database, true));
    }

    /**
     * Redis prefix getter
     *
     * @return string
     */
    public function getPrefix() {
        return $this->prefix;
    }

    /**
     * Redis prefix setter
     *
     * @param string $prefix
     * @return RedisDSN
     * @throws RedisDSNException
     */
    public function setPrefix($prefix) {
        if (is_string($prefix)) {
            $this->prefix = $prefix;
            return $this;
        }

        throw new RedisDSNException("Invalid prefix: " . var_export($prefix, true));
    }

    /**
     * Redis timeout getter
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
        return "redis://" . $this->getHost() . ":" . $this->getPort() . "/" . $this->getDatabase() . "/?timeout=" . $this->getTimeout() . "&persistent=" . ($this->getPersistent() ? "true" : "false") . "&prefix=" . $this->getPrefix();
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
 * RedisDSNException
 *
 * @package RedisBundle
 */
class RedisDSNException extends \Exception {

}