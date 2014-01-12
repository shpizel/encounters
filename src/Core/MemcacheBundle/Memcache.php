<?php
namespace Core\MemcacheBundle;

/**
 * Memcache
 *
 * @package MemcacheBundle
 */
class Memcache extends \Memcached {

    protected

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
     * @throws MemcacheException
     */
    public function setMetricsEnabled($enabled) {
        if (!is_bool($enabled)) {
            throw new MemcacheException("Invalid param");
        }

        $this->metricsEnabled = $enabled;
        return $this;
    }

    /**
     * Конструктор
     *
     * @param $servers
     */
    public function __construct($servers) {
        parent::__construct();

        foreach ($servers as $server) {
            $this->addServer(
                $server['host'],
                $server['port'],
                $server['weight']
            );
        }
    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Retrieve an item
     * @link http://php.net/manual/en/memcached.get.php
     * @param string $key <p>
     * The key of the item to retrieve.
     * </p>
     * @param $cache_cb [optional] <p>
     * Read-through caching callback or <b>NULL</b>.
     * </p>
     * @param float $cas_token [optional] <p>
     * The variable to store the CAS token in.
     * </p>
     * @return mixed the value stored in the cache or <b>FALSE</b> otherwise.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_NOTFOUND</b> if the key does not exist.
     */
    public function get($key, $cache_cb = null, &$cas_token = null) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Retrieve an item from a specific server
     * @link http://php.net/manual/en/memcached.getbykey.php
     * @param string $server_key <p>
     * The key identifying the server to store the value on.
     * </p>
     * @param string $key <p>
     * The key of the item to fetch.
     * </p>
     * @param $cache_cb [optional] <p>
     * Read-through caching callback or <b>NULL</b>
     * </p>
     * @param float $cas_token [optional] <p>
     * The variable to store the CAS token in.
     * </p>
     * @return mixed the value stored in the cache or <b>FALSE</b> otherwise.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_NOTFOUND</b> if the key does not exist.
     */
    public function getByKey($server_key, $key, $cache_cb = null, &$cas_token = null) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Retrieve multiple items
     * @link http://php.net/manual/en/memcached.getmulti.php
     * @param array $keys <p>
     * Array of keys to retrieve.
     * </p>
     * @param array $cas_tokens [optional] <p>
     * The variable to store the CAS tokens for the found items.
     * </p>
     * @param int $flags [optional] <p>
     * The flags for the get operation.
     * </p>
     * @return mixed the array of found items or <b>FALSE</b> on failure.
     * Use <b>Memcached::getResultCode</b> if necessary.
     */
    public function getMulti (array $keys, &$cas_tokens = null, $flags = null) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Retrieve multiple items from a specific server
     * @link http://php.net/manual/en/memcached.getmultibykey.php
     * @param string $server_key <p>
     * The key identifying the server to store the value on.
     * </p>
     * @param array $keys <p>
     * Array of keys to retrieve.
     * </p>
     * @param string $cas_tokens [optional] <p>
     * The variable to store the CAS tokens for the found items.
     * </p>
     * @param int $flags [optional] <p>
     * The flags for the get operation.
     * </p>
     * @return array the array of found items or <b>FALSE</b> on failure.
     * Use <b>Memcached::getResultCode</b> if necessary.
     */
    public function getMultiByKey ($server_key, array $keys, &$cas_tokens = null, $flags = null) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Request multiple items
     * @link http://php.net/manual/en/memcached.getdelayed.php
     * @param array $keys <p>
     * Array of keys to request.
     * </p>
     * @param bool $with_cas [optional] <p>
     * Whether to request CAS token values also.
     * </p>
     * @param $value_cb [optional] <p>
     * The result callback or <b>NULL</b>.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * Use <b>Memcached::getResultCode</b> if necessary.
     */
    public function getDelayed(array $keys, $with_cas = null, $value_cb = null) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Request multiple items from a specific server
     * @link http://php.net/manual/en/memcached.getdelayedbykey.php
     * @param string $server_key <p>
     * The key identifying the server to store the value on.
     * </p>
     * @param array $keys <p>
     * Array of keys to request.
     * </p>
     * @param bool $with_cas [optional] <p>
     * Whether to request CAS token values also.
     * </p>
     * @param $value_cb [optional] <p>
     * The result callback or <b>NULL</b>.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * Use <b>Memcached::getResultCode</b> if necessary.
     */
    public function getDelayedByKey($server_key, array $keys, $with_cas = null, $value_cb = null) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Fetch the next result
     * @link http://php.net/manual/en/memcached.fetch.php
     * @return array the next result or <b>FALSE</b> otherwise.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_END</b> if result set is exhausted.
     */
    public function fetch() {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Fetch all the remaining results
     * @link http://php.net/manual/en/memcached.fetchall.php
     * @return array the results or <b>FALSE</b> on failure.
     * Use <b>Memcached::getResultCode</b> if necessary.
     */
    public function fetchAll() {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Store an item
     * @link http://php.net/manual/en/memcached.set.php
     * @param string $key <p>
     * The key under which to store the value.
     * </p>
     * @param mixed $value <p>
     * The value to store.
     * </p>
     * @param int $expiration [optional] <p>
     * The expiration time, defaults to 0. See Expiration Times for more info.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * Use <b>Memcached::getResultCode</b> if necessary.
     */
    public function set($key, $value, $expiration = null) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Store an item on a specific server
     * @link http://php.net/manual/en/memcached.setbykey.php
     * @param string $server_key <p>
     * The key identifying the server to store the value on.
     * </p>
     * @param string $key <p>
     * The key under which to store the value.
     * </p>
     * @param mixed $value <p>
     * The value to store.
     * </p>
     * @param int $expiration [optional] <p>
     * The expiration time, defaults to 0. See Expiration Times for more info.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * Use <b>Memcached::getResultCode</b> if necessary.
     */
    public function setByKey($server_key, $key, $value, $expiration = null) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * @param $key
     * @param $expiration
     */
    public function touch($key, $expiration) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * @param $server_key
     * @param $key
     * @param $expiration
     */
    public function touchByKey($server_key, $key, $expiration) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Store multiple items
     * @link http://php.net/manual/en/memcached.setmulti.php
     * @param array $items <p>
     * An array of key/value pairs to store on the server.
     * </p>
     * @param int $expiration [optional] <p>
     * The expiration time, defaults to 0. See Expiration Times for more info.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * Use <b>Memcached::getResultCode</b> if necessary.
     */
    public function setMulti(array $items, $expiration = null) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Store multiple items on a specific server
     * @link http://php.net/manual/en/memcached.setmultibykey.php
     * @param string $server_key <p>
     * The key identifying the server to store the value on.
     * </p>
     * @param array $items <p>
     * An array of key/value pairs to store on the server.
     * </p>
     * @param int $expiration [optional] <p>
     * The expiration time, defaults to 0. See Expiration Times for more info.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * Use <b>Memcached::getResultCode</b> if necessary.
     */
    public function setMultiByKey($server_key, array $items, $expiration = null) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Compare and swap an item
     * @link http://php.net/manual/en/memcached.cas.php
     * @param float $cas_token <p>
     * Unique value associated with the existing item. Generated by memcache.
     * </p>
     * @param string $key <p>
     * The key under which to store the value.
     * </p>
     * @param mixed $value <p>
     * The value to store.
     * </p>
     * @param int $expiration [optional] <p>
     * The expiration time, defaults to 0. See Expiration Times for more info.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_DATA_EXISTS</b> if the item you are trying
     * to store has been modified since you last fetched it.
     */
    public function cas($cas_token, $key, $value, $expiration = null) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Compare and swap an item on a specific server
     * @link http://php.net/manual/en/memcached.casbykey.php
     * @param float $cas_token <p>
     * Unique value associated with the existing item. Generated by memcache.
     * </p>
     * @param string $server_key <p>
     * The key identifying the server to store the value on.
     * </p>
     * @param string $key <p>
     * The key under which to store the value.
     * </p>
     * @param mixed $value <p>
     * The value to store.
     * </p>
     * @param int $expiration [optional] <p>
     * The expiration time, defaults to 0. See Expiration Times for more info.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_DATA_EXISTS</b> if the item you are trying
     * to store has been modified since you last fetched it.
     */
    public function casByKey($cas_token, $server_key, $key, $value, $expiration = null) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Add an item under a new key
     * @link http://php.net/manual/en/memcached.add.php
     * @param string $key <p>
     * The key under which to store the value.
     * </p>
     * @param mixed $value <p>
     * The value to store.
     * </p>
     * @param int $expiration [optional] <p>
     * The expiration time, defaults to 0. See Expiration Times for more info.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_NOTSTORED</b> if the key already exists.
     */
    public function add($key, $value, $expiration = null) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Add an item under a new key on a specific server
     * @link http://php.net/manual/en/memcached.addbykey.php
     * @param string $server_key <p>
     * The key identifying the server to store the value on.
     * </p>
     * @param string $key <p>
     * The key under which to store the value.
     * </p>
     * @param mixed $value <p>
     * The value to store.
     * </p>
     * @param int $expiration [optional] <p>
     * The expiration time, defaults to 0. See Expiration Times for more info.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_NOTSTORED</b> if the key already exists.
     */
    public function addByKey($server_key, $key, $value, $expiration = null) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Append data to an existing item
     * @link http://php.net/manual/en/memcached.append.php
     * @param string $key <p>
     * The key under which to store the value.
     * </p>
     * @param string $value <p>
     * The string to append.
     * </p>
     * @param int $expiration <p>
     * Expiration
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_NOTSTORED</b> if the key does not exist.
     */
    public function append($key, $value, $expiration = NULL) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Append data to an existing item on a specific server
     * @link http://php.net/manual/en/memcached.appendbykey.php
     * @param string $server_key <p>
     * The key identifying the server to store the value on.
     * </p>
     * @param string $key <p>
     * The key under which to store the value.
     * </p>
     * @param string $value <p>
     * The string to append.
     * </p>
     * @param int $expiration <p>
     * Expiration
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_NOTSTORED</b> if the key does not exist.
     */
    public function appendByKey($server_key, $key, $value, $expiration = NULL) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Prepend data to an existing item
     * @link http://php.net/manual/en/memcached.prepend.php
     * @param string $key <p>
     * The key of the item to prepend the data to.
     * </p>
     * @param string $value <p>
     * The string to prepend.
     * </p>
     * @param int $expiration <p>
     * Expiration
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_NOTSTORED</b> if the key does not exist.
     */
    public function prepend($key, $value, $expiration = NULL) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Prepend data to an existing item on a specific server
     * @link http://php.net/manual/en/memcached.prependbykey.php
     * @param string $server_key <p>
     * The key identifying the server to store the value on.
     * </p>
     * @param string $key <p>
     * The key of the item to prepend the data to.
     * </p>
     * @param string $value <p>
     * The string to prepend.
     * </p>
     * @param int $expiration <p>
     * Expiration
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_NOTSTORED</b> if the key does not exist.
     */
    public function prependByKey($server_key, $key, $value, $expiration = NULL) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Replace the item under an existing key
     * @link http://php.net/manual/en/memcached.replace.php
     * @param string $key <p>
     * The key under which to store the value.
     * </p>
     * @param mixed $value <p>
     * The value to store.
     * </p>
     * @param int $expiration [optional] <p>
     * The expiration time, defaults to 0. See Expiration Times for more info.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_NOTSTORED</b> if the key does not exist.
     */
    public function replace($key, $value, $expiration = null) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Replace the item under an existing key on a specific server
     * @link http://php.net/manual/en/memcached.replacebykey.php
     * @param string $server_key <p>
     * The key identifying the server to store the value on.
     * </p>
     * @param string $key <p>
     * The key under which to store the value.
     * </p>
     * @param mixed $value <p>
     * The value to store.
     * </p>
     * @param int $expiration [optional] <p>
     * The expiration time, defaults to 0. See Expiration Times for more info.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_NOTSTORED</b> if the key does not exist.
     */
    public function replaceByKey($server_key, $key, $value, $expiration = null) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Delete an item
     * @link http://php.net/manual/en/memcached.delete.php
     * @param string $key <p>
     * The key to be deleted.
     * </p>
     * @param int $time [optional] <p>
     * The amount of time the server will wait to delete the item.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_NOTFOUND</b> if the key does not exist.
     */
    public function delete($key, $time = 0) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * @param $keys
     * @param $time [optional]
     */
    public function deleteMulti($keys, $time = NULL) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Delete an item from a specific server
     * @link http://php.net/manual/en/memcached.deletebykey.php
     * @param string $server_key <p>
     * The key identifying the server to store the value on.
     * </p>
     * @param string $key <p>
     * The key to be deleted.
     * </p>
     * @param int $time [optional] <p>
     * The amount of time the server will wait to delete the item.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_NOTFOUND</b> if the key does not exist.
     */
    public function deleteByKey($server_key, $key, $time = NULL) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * @param $server_key
     * @param $keys
     * @param $time [optional]
     */
    public function deleteMultiByKey($server_key, $keys, $time = NULL) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Increment numeric item's value
     * @link http://php.net/manual/en/memcached.increment.php
     * @param string $key <p>
     * The key of the item to increment.
     * </p>
     * @param int $offset [optional] <p>
     * The amount by which to increment the item's value.
     * </p>
     * @return int new item's value on success or <b>FALSE</b> on failure.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_NOTFOUND</b> if the key does not exist.
     */
    public function increment($key, $offset = NULL, $initial_value = NULL, $expiry = NULL) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Decrement numeric item's value
     * @link http://php.net/manual/en/memcached.decrement.php
     * @param string $key <p>
     * The key of the item to decrement.
     * </p>
     * @param int $offset [optional] <p>
     * The amount by which to decrement the item's value.
     * </p>
     * @return int item's new value on success or <b>FALSE</b> on failure.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_NOTFOUND</b> if the key does not exist.
     */
    public function decrement($key, $offset = NULL, $initial_value = NULL, $expiry = NULL) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * @param $server_key
     * @param $key
     * @param $offset [optional]
     * @param $initial_value [optional]
     * @param $expiry [optional]
     */
    public function incrementByKey($server_key, $key, $offset = NULL, $initial_value = NULL, $expiry = NULL) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * @param $server_key
     * @param $key
     * @param $offset [optional]
     * @param $initial_value [optional]
     * @param $expiry [optional]
     */
    public function decrementByKey($server_key, $key, $offset = NULL, $initial_value = NULL, $expiry = NULL) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Invalidate all items in the cache
     * @link http://php.net/manual/en/memcached.flush.php
     * @param int $delay [optional] <p>
     * Numer of seconds to wait before invalidating the items.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * Use <b>Memcached::getResultCode</b> if necessary.
     */
    public function flush($delay = 0) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array('parent', __FUNCTION__), func_get_args());

        if ($this->metricsEnabled) {
            $this->metrics['requests'][] = array(
                'method'  => __FUNCTION__,
                'args'  => func_get_args(),
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            $this->metrics['timeout']+=$timeout;
        }

        return $ret;
    }
}

/**
 * MemcacheException
 *
 * @package MemcacheBundle
 */
class MemcacheException extends \Exception
{

}