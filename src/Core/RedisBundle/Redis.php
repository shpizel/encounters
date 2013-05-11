<?php
namespace Core\RedisBundle;

/**
 * Redis
 *
 * @package RedisBundle
 */
class Redis {

    const

        /**
         * Single mode
         *
         * @var int
         */
        SINGLE_MODE = 1,

        /**
         * Multi mode
         *
         * @var int
         */
        MULTI_MODE = 2
    ;

     private

         /**
          * Ноды редиса
          *
          * @var array
          */
         $nodes = array(),

         /**
          * Пул соединений
          *
          * @var array
          */
         $connections = array(),

         /**
          * Режим работы класса
          *
          * @var int
          */
         $mode = self::SINGLE_MODE,

         /**
          * Multi queue
          *
          * @var array
          */
         $multiQueue = array(),

         /**
          * Метрики использования
          *
          * @var array
          */
         $metrics = array(
             'requests' => array(),
             'timeout'  => 0,
         )
     ;

    /**
     * Конструктор
     *
     * @param array $nodes = array(array(
     *     host: ...
     *     port: ...
     *     timeout: ...
     *     options: ...
     *         persistent: true
     *         database: 0
     *         prefix: false
     * ))
     */
    public function __construct(array $nodes) {
        $this->nodes = array_map(function($nodeArray) {
            return RedisDSN::getDSNFromArray($nodeArray);
        }, $nodes);
    }

    /**
     * Returns usage metrics
     *
     * @return array
     */
    public function getMetrics() {
        return $this->metrics;
    }

    public function clearMetrics() {
        $this->metrics = array(
            'requests' => array(),
            'timeout'  => 0,
        );
    }

    /**
     * Redis nodes getter
     *
     * @return array
     */
    public function getNodes() {
        return $this->nodes;
    }

    /**
     * @param RedisDSN $node
     */
    public function getNodeConnection(RedisDSN $node) {
        $hostKey = (string) $node;
        if (isset($this->connections[$hostKey])) {
            return $this->connections[$hostKey];
        } else {
            $Redis = new \Redis;
            $connectFunction = ($node->getPersistent()) ? 'pconnect' : 'connect';
            if ($Redis->$connectFunction($node->getHost(), (int) $node->getPort(), (float) $node->getTimeout(), $hostKey)) {
                if ($prefix = $node->getPrefix()) {
                    if (!$Redis->setOption(\Redis::OPT_PREFIX, $prefix)) {
                        throw new RedisException("Could not set prefix for Redis node");
                    }
                }

                if ($database = $node->getDatabase()) {
                    if (!$Redis->select($database)) {
                        throw new RedisException("Could not select database for Redis node");
                    }
                }

                return $this->connections[$hostKey] = $Redis;
            }
        }

        throw new RedisException("Could not connect to Redis node");
    }

    /**
     * Connect to redis node by key
     *
     * @param str $key
     * @return \Redis
     */
    public function getNodeConnectionByKey($key) {
        return $this->getNodeConnection($this->getDSNByKey($key));
    }

    /**
     * Connection to redis node by number
     *
     * @param int $number
     */
    public function getNodeConnectionByNodeNumber($number) {
        return $this->getNodeConnection($this->nodes[$number]);
    }

    /**
     * Get DSN from
     *
     * @param str $key
     * @return RedisDSN
     */
    public function getDSNByKey($key) {
        return $this->nodes[$this->getNodeNumberByKey($key)];
    }

    /**
     * Returns node number by key
     *
     * @param $key
     * @return mixed
     */
    public function getNodeNumberByKey($key) {
        return crc32($key) % count($this->nodes);
    }

    /**
     * Redis nodes setter
     *
     * @param array $nodes
     */
    public function setNodes(array $nodes) {
        $this->nodes = $nodes;
        $this->closeConnections();
    }

    /**
     * Закрывает все открытые соединения с редисами
     *
     * @return null
     */
    public function closeConnections() {
        foreach ($this->connections as $connection) {
            $connection->close();
        }

        $this->connections = array();
    }

    /**
     * Get the value related to the specified key
     *
     * @param   string  $key
     * @return  string|bool: If key didn't exist, FALSE is returned. Otherwise, the value related to this key is returned.
     * @link    http://redis.io/commands/get
     * @example $redis->get('key');
     */
    public function get($key) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }


    /**
     * Set the string value in argument as value of the key.
     *
     * @param   string  $key
     * @param   string  $value
     * @param   float   $timeout    Calling setex() is preferred if you want a timeout.
     * @return  bool:   TRUE if the command is successful.
     * @link    http://redis.io/commands/set
     * @example $redis->set('key', 'value');
     */
    public function set($key, $value, $timeout = 0.0) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Set the string value in argument as value of the key, with a time to live.
     *
     * @param   string  $key
     * @param   int     $ttl
     * @param   string  $value
     * @return  bool:   TRUE if the command is successful.
     * @link    http://redis.io/commands/setex
     * @example $redis->setex('key', 3600, 'value'); // sets key → value, with 1h TTL.
     */
    public function setex($key, $ttl, $value) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Set the string value in argument as value of the key if the key doesn't already exist in the database.
     *
     * @param   string  $key
     * @param   string  $value
     * @return  bool:   TRUE in case of success, FALSE in case of failure.
     * @link    http://redis.io/commands/setnx
     * @example
     * <pre>
     * $redis->setnx('key', 'value');   // return TRUE
     * $redis->setnx('key', 'value');   // return FALSE
     * </pre>
     */
    public function setnx($key, $value) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Remove specified keys.
     *
     * @param   int|array   $key1 An array of keys, or an undefined number of parameters, each a key: key1 key2 key3 ... keyN
     * @param   string      $key2 ...
     * @param   string      $key3 ...
     * @return int Number of keys deleted.
     * @link    http://redis.io/commands/del
     * @example
     * <pre>
     * $redis->set('key1', 'val1');
     * $redis->set('key2', 'val2');
     * $redis->set('key3', 'val3');
     * $redis->set('key4', 'val4');
     * $redis->delete('key1', 'key2');          // return 2
     * $redis->delete(array('key3', 'key4'));   // return 2
     * </pre>
     */
    public function del($key1, $key2 = null, $key3 = null) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            throw new RedisException(__CLASS__ . "::" . __FUNCTION__ . " is not supported for multi-mode");
        }

        $counter = 0;
        $chunks = array();
        foreach (func_get_args() as $key) {
            $nodeNumber = $this->getNodeNumberByKey($key);
            if (isset($chunks[$nodeNumber])) {
                $chunks[$nodeNumber][] = $key;
            } else {
                $chunks[$nodeNumber] = array($key);
            }
        }

        foreach ($chunks as $nodeNumber=>$args) {
            $counter+= call_user_func_array(array($this->getNodeConnectionByNodeNumber($nodeNumber), __FUNCTION__), $args);
        }

        return $counter;
    }

    /**
     * @see del()
     * @param $key1
     * @param null $key2
     * @param null $key3
     */
    public function delete($key1, $key2 = null, $key3 = null) {
        return call_user_func_array(array($this, 'del'), func_get_args());
    }

    /**
     * Enter and exit transactional mode.
     *
     * @internal param Redis::MULTI|Redis::PIPELINE
     * Defaults to Redis::MULTI.
     * A Redis::MULTI block of commands runs as a single transaction;
     * a Redis::PIPELINE block is simply transmitted faster to the server, but without any guarantee of atomicity.
     * discard cancels a transaction.
     * @return Redis returns the Redis instance and enters multi-mode.
     * Once in multi-mode, all subsequent method calls return the same object until exec() is called.
     * @link    http://redis.io/commands/multi
     * @example
     * <pre>
     * $ret = $redis->multi()
     *      ->set('key1', 'val1')
     *      ->get('key1')
     *      ->set('key2', 'val2')
     *      ->get('key2')
     *      ->exec();
     *
     * //$ret == array (
     * //    0 => TRUE,
     * //    1 => 'val1',
     * //    2 => TRUE,
     * //    3 => 'val2');
     * </pre>
     */
    public function multi() {
        $this->mode = self::MULTI_MODE;
        $this->multiQueue = array();
        return $this;
    }

    /**
     * @see multi()
     * @link    http://redis.io/commands/exec
     */
    public function exec() {
        $startTime = microtime(true);

        $result = array();
        $chunks = array();

        foreach ($this->multiQueue as $item) {
            $function = $item['function'];
            $args = $item['args'];
            $key = $item['key'];

            $nodeNumber = $this->getNodeNumberByKey($key);
            $resultKey = $function . ":" . md5(http_build_query($args));
            $result[$resultKey] = false;

            if (isset($chunks[$nodeNumber])) {
                $chunks[$nodeNumber][$resultKey] = array(
                    'function' => $function,
                    'args'     => $args,
                );
            } else {
                $chunks[$nodeNumber] = array(
                    $resultKey => array(
                        'function' => $function,
                        'args'     => $args,
                    )
                );
            }
        }

        foreach ($chunks as $nodeNumber => $queue) {
            $_keys = array_keys($queue);
            $nodeConnection = $this->getNodeConnectionByNodeNumber($nodeNumber);

            $nodeConnection->multi();
            foreach ($queue as $queueItem) {
                call_user_func_array(array($nodeConnection, $queueItem['function']), $queueItem['args']);
            }
            $nodeResult = $nodeConnection->exec();

            foreach ($_keys as $index=>$_key) {
                $result[$_key] = $nodeResult[$index];
            }
        }

        $this->mode = self::SINGLE_MODE;
        return array_values($result);
    }

    /**
     * @see multi()
     * @link    http://redis.io/commands/discard
     */
    public function discard() {
        $this->multiQueue = array();
        $this->mode = self::SINGLE_MODE;
    }

    /**
     * Subscribe to channels. Warning: this function will probably change in the future.
     *
     * @param array             $channels an array of channels to subscribe to
     * @param string | array    $callback either a string or an array($instance, 'method_name').
     * The callback function receives 3 parameters: the redis instance, the channel name, and the message.
     * @link    http://redis.io/commands/subscribe
     * @example
     * <pre>
     * function f($redis, $chan, $msg) {
     *  switch($chan) {
     *      case 'chan-1':
     *          ...
     *          break;
     *
     *      case 'chan-2':
     *                     ...
     *          break;
     *
     *      case 'chan-2':
     *          ...
     *          break;
     *      }
     * }
     *
     * $redis->subscribe(array('chan-1', 'chan-2', 'chan-3'), 'f'); // subscribe to 3 chans
     * </pre>
     */
    public function subscribe($channels, $callback) {
        throw new RedisException(__FUNCTION__ . " not implemented yet");
    }

    /**
     * Publish messages to channels. Warning: this function will probably change in the future.
     *
     * @param   string $channel a channel to publish to
     * @param   string $message string
     * @link    http://redis.io/commands/publish
     * @example $redis->publish('chan-1', 'hello, world!'); // send message.
     */
    public function publish($channel, $message) {
        throw new RedisException(__FUNCTION__ . " not implemented yet");
    }

    /**
     * Verify if the specified key exists.
     *
     * @param   string $key
     * @return  bool: If the key exists, return TRUE, otherwise return FALSE.
     * @link    http://redis.io/commands/exists
     * @example
     * <pre>
     * $redis->set('key', 'value');
     * $redis->exists('key');               //  TRUE
     * $redis->exists('NonExistingKey');    // FALSE
     * </pre>
     */
    public function exists($key) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Increment the number stored at key by one.
     *
     * @param   string $key
     * @return  int    the new value
     * @link    http://redis.io/commands/incr
     * @example
     * <pre>
     * $redis->incr('key1'); // key1 didn't exists, set to 0 before the increment and now has the value 1
     * $redis->incr('key1'); // 2
     * $redis->incr('key1'); // 3
     * $redis->incr('key1'); // 4
     * </pre>
     */
    public function incr($key) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Increment the float value of a key by the given amount
     *
     * @param   string  $key
     * @param   float   $increment
     * @return  float
     * @link    http://redis.io/commands/incrbyfloat
     * @example
     * <pre>
     * $redis = new Redis();
     * $redis->connect('127.0.0.1');
     * $redis->set('x', 3);
     * var_dump( $redis->incrByFloat('x', 1.5) );   // float(4.5)
     *
     * // ! SIC
     * var_dump( $redis->get('x') );                // string(3) "4.5"
     * </pre>
     */
    public function incrByFloat($key, $increment) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Increment the number stored at key by one. If the second argument is filled, it will be used as the integer
     * value of the increment.
     *
     * @param   string    $key    key
     * @param   int       $value  value that will be added to key (only for incrBy)
     * @return  int         the new value
     * @link    http://redis.io/commands/incrby
     * @example
     * <pre>
     * $redis->incr('key1');        // key1 didn't exists, set to 0 before the increment and now has the value 1
     * $redis->incr('key1');        // 2
     * $redis->incr('key1');        // 3
     * $redis->incr('key1');        // 4
     * $redis->incrBy('key1', 10);  // 14
     * </pre>
     */
    public function incrBy($key, $value) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Decrement the number stored at key by one.
     *
     * @param   string $key
     * @return  int    the new value
     * @link    http://redis.io/commands/decr
     * @example
     * <pre>
     * $redis->decr('key1'); // key1 didn't exists, set to 0 before the increment and now has the value -1
     * $redis->decr('key1'); // -2
     * $redis->decr('key1'); // -3
     * </pre>
     */
    public function decr($key) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Decrement the number stored at key by one. If the second argument is filled, it will be used as the integer
     * value of the decrement.
     *
     * @param   string    $key
     * @param   int       $value  that will be substracted to key (only for decrBy)
     * @return  int       the new value
     * @link    http://redis.io/commands/decrby
     * @example
     * <pre>
     * $redis->decr('key1');        // key1 didn't exists, set to 0 before the increment and now has the value -1
     * $redis->decr('key1');        // -2
     * $redis->decr('key1');        // -3
     * $redis->decrBy('key1', 10);  // -13
     * </pre>
     */
    public function decrBy($key, $value) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Adds the string values to the head (left) of the list. Creates the list if the key didn't exist.
     * If the key exists and is not a list, FALSE is returned.
     *
     * @param   string $key
     * @param   string $value1  String, value to push in key
     * @param   string $value2  Optional
     * @param   string $valueN  Optional
     * @return  int    The new length of the list in case of success, FALSE in case of Failure.
     * @link    http://redis.io/commands/lpush
     * @example
     * <pre>
     * $redis->lPush('l', 'v1', 'v2', 'v3', 'v4')   // int(4)
     * var_dump( $redis->lRange('l', 0, -1) );
     * //// Output:
     * // array(4) {
     * //   [0]=> string(2) "v4"
     * //   [1]=> string(2) "v3"
     * //   [2]=> string(2) "v2"
     * //   [3]=> string(2) "v1"
     * // }
     * </pre>
     */
    public function lPush($key, $value1, $value2 = null, $valueN = null) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Adds the string values to the tail (right) of the list. Creates the list if the key didn't exist.
     * If the key exists and is not a list, FALSE is returned.
     *
     * @param   string  $key
     * @param   string  $value1 String, value to push in key
     * @param   string  $value2 Optional
     * @param   string  $valueN Optional
     * @return  int     The new length of the list in case of success, FALSE in case of Failure.
     * @link    http://redis.io/commands/rpush
     * @example
     * <pre>
     * $redis->rPush('l', 'v1', 'v2', 'v3', 'v4');    // int(4)
     * var_dump( $redis->lRange('l', 0, -1) );
     * //// Output:
     * // array(4) {
     * //   [0]=> string(2) "v1"
     * //   [1]=> string(2) "v2"
     * //   [2]=> string(2) "v3"
     * //   [3]=> string(2) "v4"
     * // }
     * </pre>
     */
    public function rPush($key, $value1, $value2 = null, $valueN = null) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Adds the string value to the head (left) of the list if the list exists.
     *
     * @param   string  $key
     * @param   string  $value String, value to push in key
     * @return  int     The new length of the list in case of success, FALSE in case of Failure.
     * @link    http://redis.io/commands/lpushx
     * @example
     * <pre>
     * $redis->delete('key1');
     * $redis->lPushx('key1', 'A');     // returns 0
     * $redis->lPush('key1', 'A');      // returns 1
     * $redis->lPushx('key1', 'B');     // returns 2
     * $redis->lPushx('key1', 'C');     // returns 3
     * // key1 now points to the following list: [ 'A', 'B', 'C' ]
     * </pre>
     */
    public function lPushx($key, $value) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Adds the string value to the tail (right) of the list if the ist exists. FALSE in case of Failure.
     *
     * @param   string  $key
     * @param   string  $value String, value to push in key
     * @return  int     The new length of the list in case of success, FALSE in case of Failure.
     * @link    http://redis.io/commands/rpushx
     * @example
     * <pre>
     * $redis->delete('key1');
     * $redis->rPushx('key1', 'A'); // returns 0
     * $redis->rPush('key1', 'A'); // returns 1
     * $redis->rPushx('key1', 'B'); // returns 2
     * $redis->rPushx('key1', 'C'); // returns 3
     * // key1 now points to the following list: [ 'A', 'B', 'C' ]
     * </pre>
     */
    public function rPushx($key, $value) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Returns and removes the first element of the list.
     *
     * @param   string $key
     * @return  string if command executed successfully BOOL FALSE in case of failure (empty list)
     * @link    http://redis.io/commands/lpop
     * @example
     * <pre>
     * $redis->rPush('key1', 'A');
     * $redis->rPush('key1', 'B');
     * $redis->rPush('key1', 'C');  // key1 => [ 'A', 'B', 'C' ]
     * $redis->lPop('key1');        // key1 => [ 'B', 'C' ]
     * </pre>
     */
    public function lPop($key) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Returns and removes the last element of the list.
     *
     * @param   string $key
     * @return  string if command executed successfully BOOL FALSE in case of failure (empty list)
     * @link    http://redis.io/commands/rpop
     * @example
     * <pre>
     * $redis->rPush('key1', 'A');
     * $redis->rPush('key1', 'B');
     * $redis->rPush('key1', 'C');  // key1 => [ 'A', 'B', 'C' ]
     * $redis->rPop('key1');        // key1 => [ 'A', 'B' ]
     * </pre>
     */
    public function rPop($key) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Returns the size of a list identified by Key. If the list didn't exist or is empty,
     * the command returns 0. If the data type identified by Key is not a list, the command return FALSE.
     *
     * @param   string  $key
     * @return  int     The size of the list identified by Key exists.
     * bool FALSE if the data type identified by Key is not list
     * @link    http://redis.io/commands/llen
     * @example
     * <pre>
     * $redis->rPush('key1', 'A');
     * $redis->rPush('key1', 'B');
     * $redis->rPush('key1', 'C');  // key1 => [ 'A', 'B', 'C' ]
     * $redis->lLen('key1');       // 3
     * $redis->rPop('key1');
     * $redis->lLen('key1');       // 2
     * </pre>
     */
    public function lLen($key) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * @see     lLen()
     * @param   string    $key
     * @param   int       $index
     * @link    http://redis.io/commands/llen
     */
    public function lSize($key) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }


    /**
     * Return the specified element of the list stored at the specified key.
     * 0 the first element, 1 the second ... -1 the last element, -2 the penultimate ...
     * Return FALSE in case of a bad index or a key that doesn't point to a list.
     * @param string    $key
     * @param int       $index
     * @return String the element at this index
     * Bool FALSE if the key identifies a non-string data type, or no value corresponds to this index in the list Key.
     * @link    http://redis.io/commands/lindex
     * @example
     * <pre>
     * $redis->rPush('key1', 'A');
     * $redis->rPush('key1', 'B');
     * $redis->rPush('key1', 'C');  // key1 => [ 'A', 'B', 'C' ]
     * $redis->lGet('key1', 0);     // 'A'
     * $redis->lGet('key1', -1);    // 'C'
     * $redis->lGet('key1', 10);    // `FALSE`
     * </pre>
     */
    public function lIndex($key, $index) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * @see lIndex()
     * @param   string    $key
     * @param   int       $index
     * @link    http://redis.io/commands/lindex
     */
    public function lGet($key, $index) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Set the list at index with the new value.
     *
     * @param string    $key
     * @param int       $index
     * @param string    $value
     * @return BOOL TRUE if the new value is setted. FALSE if the index is out of range, or data type identified by key
     * is not a list.
     * @link    http://redis.io/commands/lset
     * @example
     * <pre>
     * $redis->rPush('key1', 'A');
     * $redis->rPush('key1', 'B');
     * $redis->rPush('key1', 'C');  // key1 => [ 'A', 'B', 'C' ]
     * $redis->lGet('key1', 0);     // 'A'
     * $redis->lSet('key1', 0, 'X');
     * $redis->lGet('key1', 0);     // 'X'
     * </pre>
     */
    public function lSet($key, $index, $value) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }


    /**
     * Returns the specified elements of the list stored at the specified key in
     * the range [start, end]. start and stop are interpretated as indices: 0 the first element,
     * 1 the second ... -1 the last element, -2 the penultimate ...
     * @param   string  $key
     * @param   int     $start
     * @param   int     $end
     * @return  array containing the values in specified range.
     * @link    http://redis.io/commands/lrange
     * @example
     * <pre>
     * $redis->rPush('key1', 'A');
     * $redis->rPush('key1', 'B');
     * $redis->rPush('key1', 'C');
     * $redis->lRange('key1', 0, -1); // array('A', 'B', 'C')
     * </pre>
     */
    public function lRange($key, $start, $end) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * @see lRange()
     * @link http://redis.io/commands/lrange
     * @param string    $key
     * @param int       $start
     * @param int       $end
     */
    public function lGetRange($key, $start, $end) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }


    /**
     * Trims an existing list so that it will contain only a specified range of elements.
     *
     * @param string    $key
     * @param int       $start
     * @param int       $stop
     * @return array    Bool return FALSE if the key identify a non-list value.
     * @link        http://redis.io/commands/ltrim
     * @example
     * <pre>
     * $redis->rPush('key1', 'A');
     * $redis->rPush('key1', 'B');
     * $redis->rPush('key1', 'C');
     * $redis->lRange('key1', 0, -1); // array('A', 'B', 'C')
     * $redis->lTrim('key1', 0, 1);
     * $redis->lRange('key1', 0, -1); // array('A', 'B')
     * </pre>
     */
    public function lTrim($key, $start, $stop) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Removes the first count occurences of the value element from the list.
     * If count is zero, all the matching elements are removed. If count is negative,
     * elements are removed from tail to head.
     *
     * @param   string  $key
     * @param   string  $value
     * @param   int     $count
     * @return  int     the number of elements to remove
     * bool FALSE if the value identified by key is not a list.
     * @link    http://redis.io/commands/lrem
     * @example
     * <pre>
     * $redis->lPush('key1', 'A');
     * $redis->lPush('key1', 'B');
     * $redis->lPush('key1', 'C');
     * $redis->lPush('key1', 'A');
     * $redis->lPush('key1', 'A');
     *
     * $redis->lRange('key1', 0, -1);   // array('A', 'A', 'C', 'B', 'A')
     * $redis->lRem('key1', 'A', 2);    // 2
     * $redis->lRange('key1', 0, -1);   // array('C', 'B', 'A')
     * </pre>
     */
    public function lRem($key, $value, $count) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * @see lRem
     * @link    http://redis.io/commands/lremove
     * @param string    $key
     * @param string    $value
     * @param int       $count
     */
    public function lRemove($key, $value, $count) {
        $startTime = microtime(true);

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), "lRem"), func_get_args());
    }


    /**
     * Insert value in the list before or after the pivot value. the parameter options
     * specify the position of the insert (before or after). If the list didn't exists,
     * or the pivot didn't exists, the value is not inserted.
     *
     * @param   string  $key
     * @param   int     $position Redis::BEFORE | Redis::AFTER
     * @param   string  $pivot
     * @param   string  $value
     * @return  int     The number of the elements in the list, -1 if the pivot didn't exists.
     * @link    http://redis.io/commands/linsert
     * @example
     * <pre>
     * $redis->delete('key1');
     * $redis->lInsert('key1', Redis::AFTER, 'A', 'X');     // 0
     *
     * $redis->lPush('key1', 'A');
     * $redis->lPush('key1', 'B');
     * $redis->lPush('key1', 'C');
     *
     * $redis->lInsert('key1', Redis::BEFORE, 'C', 'X');    // 4
     * $redis->lRange('key1', 0, -1);                       // array('A', 'B', 'X', 'C')
     *
     * $redis->lInsert('key1', Redis::AFTER, 'C', 'Y');     // 5
     * $redis->lRange('key1', 0, -1);                       // array('A', 'B', 'X', 'C', 'Y')
     *
     * $redis->lInsert('key1', Redis::AFTER, 'W', 'value'); // -1
     * </pre>
     */
    public function lInsert($key, $position, $pivot, $value) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }


    /**
     * Adds a values to the set value stored at key. If this value is already in the set, FALSE is returned.
     *
     * @param   string  $key        Required key
     * @param   string  $value1     Required value
     * @param   string  $value2     Optional value
     * @param   string  $valueN     Optional value
     * @return  int     Number of value added
     * @link    http://redis.io/commands/sadd
     * @example
     * <pre>
     * $redis->sAdd('k', 'v1');                // int(1)
     * $redis->sAdd('k', 'v1', 'v2', 'v3');    // int(2)
     * </pre>
     */
    public function sAdd($key, $value1, $value2 = null, $valueN = null) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }


    /**
     * Removes the specified members from the set value stored at key.
     *
     * @param   string  $key
     * @param   string  $member1
     * @param   string  $member2
     * @param   string  $memberN
     * @return  int     Number of deleted values
     * @link    http://redis.io/commands/srem
     * @example
     * <pre>
     * var_dump( $redis->sAdd('k', 'v1', 'v2', 'v3') );    // int(3)
     * var_dump( $redis->sRem('k', 'v2', 'v3') );          // int(2)
     * var_dump( $redis->sMembers('k') );
     * //// Output:
     * // array(1) {
     * //   [0]=> string(2) "v1"
     * // }
     * </pre>
     */
    public function sRem($key, $member1, $member2 = null, $memberN = null) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * @see sRem()
     * @link    http://redis.io/commands/srem
     * @param   string  $key
     * @param   string  $member1
     * @param   string  $member2
     * @param   string  $memberN
     */
    public function sRemove($key, $member1, $member2 = null, $memberN = null) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }


    /**
     * Checks if value is a member of the set stored at the key key.
     *
     * @param   string  $key
     * @param   string  $value
     * @return  bool    TRUE if value is a member of the set at key key, FALSE otherwise.
     * @link    http://redis.io/commands/sismember
     * @example
     * <pre>
     * $redis->sAdd('key1' , 'set1');
     * $redis->sAdd('key1' , 'set2');
     * $redis->sAdd('key1' , 'set3'); // 'key1' => {'set1', 'set2', 'set3'}
     *
     * $redis->sIsMember('key1', 'set1'); // TRUE
     * $redis->sIsMember('key1', 'setX'); // FALSE
     * </pre>
     */
    public function sIsMember($key, $value) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * @see sIsMember()
     * @link    http://redis.io/commands/sismember
     * @param   string  $key
     * @param   string  $value
     */
    public function sContains($key, $value) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Returns the cardinality of the set identified by key.
     *
     * @param   string  $key
     * @return  int     the cardinality of the set identified by key, 0 if the set doesn't exist.
     * @link    http://redis.io/commands/scard
     * @example
     * <pre>
     * $redis->sAdd('key1' , 'set1');
     * $redis->sAdd('key1' , 'set2');
     * $redis->sAdd('key1' , 'set3');   // 'key1' => {'set1', 'set2', 'set3'}
     * $redis->sCard('key1');           // 3
     * $redis->sCard('keyX');           // 0
     * </pre>
     */
    public function sCard($key) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * @see sCard()
     * @link    http://redis.io/commands/sSize
     * @param   string  $key
     */
    public function sSize($key) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        return $this->sCard($key);
    }

    /**
     * Removes and returns a random element from the set value at Key.
     *
     * @param   string  $key
     * @return  string  "popped" value
     * bool FALSE if set identified by key is empty or doesn't exist.
     * @link    http://redis.io/commands/spop
     * @example
     * <pre>
     * $redis->sAdd('key1' , 'set1');
     * $redis->sAdd('key1' , 'set2');
     * $redis->sAdd('key1' , 'set3');   // 'key1' => {'set3', 'set1', 'set2'}
     * $redis->sPop('key1');            // 'set1', 'key1' => {'set3', 'set2'}
     * $redis->sPop('key1');            // 'set3', 'key1' => {'set2'}
     * </pre>
     */
    public function sPop($key) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }


    /**
     * Returns a random element from the set value at Key, without removing it.
     *
     * @param   string  $key
     * @return  string  value from the set
     * bool FALSE if set identified by key is empty or doesn't exist.
     * @link    http://redis.io/commands/srandmember
     * @example
     * <pre>
     * $redis->sAdd('key1' , 'set1');
     * $redis->sAdd('key1' , 'set2');
     * $redis->sAdd('key1' , 'set3');   // 'key1' => {'set3', 'set1', 'set2'}
     * $redis->sRandMember('key1');     // 'set1', 'key1' => {'set3', 'set1', 'set2'}
     * $redis->sRandMember('key1');     // 'set3', 'key1' => {'set3', 'set1', 'set2'}
     * </pre>
     */
    public function sRandMember($key) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Returns the contents of a set.
     *
     * @param   string  $key
     * @return  array   An array of elements, the contents of the set.
     * @link    http://redis.io/commands/smembers
     * @example
     * <pre>
     * $redis->delete('s');
     * $redis->sAdd('s', 'a');
     * $redis->sAdd('s', 'b');
     * $redis->sAdd('s', 'a');
     * $redis->sAdd('s', 'c');
     * var_dump($redis->sMembers('s'));
     *
     * //array(3) {
     * //  [0]=>
     * //  string(1) "c"
     * //  [1]=>
     * //  string(1) "a"
     * //  [2]=>
     * //  string(1) "b"
     * //}
     * // The order is random and corresponds to redis' own internal representation of the set structure.
     * </pre>
     */
    public function sMembers($key) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * @see sMembers()
     * @param   string  $key
     * @link    http://redis.io/commands/smembers
     */
    public function sGetMembers($key) {
        return $this->sMembers($key);
    }

    /**
     * Sets a value and returns the previous entry at that key.
     *
     * @param   string  $key
     * @param   string  $value
     * @return  string  A string, the previous value located at this key.
     * @link    http://redis.io/commands/getset
     * @example
     * <pre>
     * $redis->set('x', '42');
     * $exValue = $redis->getSet('x', 'lol');   // return '42', replaces x by 'lol'
     * $newValue = $redis->get('x')'            // return 'lol'
     * </pre>
     */
    public function getSet($key, $value) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Moves a key to a different database.
     *
     * @param   string  $key
     * @param   int     $dbindex
     * @return  bool:   TRUE in case of success, FALSE in case of failure.
     * @link    http://redis.io/commands/move
     * @example
     * <pre>
     * $redis->select(0);       // switch to DB 0
     * $redis->set('x', '42');  // write 42 to x
     * $redis->move('x', 1);    // move to DB 1
     * $redis->select(1);       // switch to DB 1
     * $redis->get('x');        // will return 42
     * </pre>
     */
    public function move($key, $dbindex) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Sets an expiration date (a timeout) on an item.
     *
     * @param   string  $key    The key that will disappear.
     * @param   int     $ttl    The key's remaining Time To Live, in seconds.
     * @return  bool:   TRUE in case of success, FALSE in case of failure.
     * @link    http://redis.io/commands/expire
     * @example
     * <pre>
     * $redis->set('x', '42');
     * $redis->setTimeout('x', 3);  // x will disappear in 3 seconds.
     * sleep(5);                    // wait 5 seconds
     * $redis->get('x');            // will return `FALSE`, as 'x' has expired.
     * </pre>
     */
    public function expire($key, $ttl) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Sets an expiration date (a timeout in milliseconds) on an item.
     *
     * @param   string  $key    The key that will disappear.
     * @param   int     $pttl   The key's remaining Time To Live, in milliseconds.
     * @return  bool:   TRUE in case of success, FALSE in case of failure.
     * @link    http://redis.io/commands/pexpire
     * @example
     * <pre>
     * $redis->set('x', '42');
     * $redis->pExpire('x', 11500); // x will disappear in 11500 milliseconds.
     * $redis->ttl('x');            // 12
     * $redis->pttl('x');           // 11500
     * </pre>
     */
    public function pExpire($key, $ttl) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * @see expire()
     * @param   string  $key
     * @param   int     $ttl
     * @link    http://redis.io/commands/expire
     */
    public function setTimeout($key, $ttl) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Sets an expiration date (a timestamp) on an item.
     *
     * @param   string  $key        The key that will disappear.
     * @param   int     $timestamp  Unix timestamp. The key's date of death, in seconds from Epoch time.
     * @return  bool:   TRUE in case of success, FALSE in case of failure.
     * @link    http://redis.io/commands/expireat
     * @example
     * <pre>
     * $redis->set('x', '42');
     * $now = time(NULL);               // current timestamp
     * $redis->expireAt('x', $now + 3); // x will disappear in 3 seconds.
     * sleep(5);                        // wait 5 seconds
     * $redis->get('x');                // will return `FALSE`, as 'x' has expired.
     * </pre>
     */
    public function expireAt($key, $timestamp) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Sets an expiration date (a timestamp) on an item. Requires a timestamp in milliseconds
     *
     * @param   string  $key        The key that will disappear.
     * @param   int     $timestamp  Unix timestamp. The key's date of death, in seconds from Epoch time.
     * @return  bool:   TRUE in case of success, FALSE in case of failure.
     * @link    http://redis.io/commands/pexpireat
     * @example
     * <pre>
     * $redis->set('x', '42');
     * $redis->pExpireAt('x', 1555555555005);
     * echo $redis->ttl('x');                       // 218270121
     * echo $redis->pttl('x');                      // 218270120575
     * </pre>
     */
    public function pExpireAt($key, $timestamp) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Describes the object pointed to by a key.
     * The information to retrieve (string) and the key (string).
     * Info can be one of the following:
     * - "encoding"
     * - "refcount"
     * - "idletime"
     *
     * @param   string  $string
     * @param   string  $key
     * @return  string  for "encoding", int for "refcount" and "idletime", FALSE if the key doesn't exist.
     * @link    http://redis.io/commands/object
     * @example
     * <pre>
     * $redis->object("encoding", "l"); // → ziplist
     * $redis->object("refcount", "l"); // → 1
     * $redis->object("idletime", "l"); // → 400 (in seconds, with a precision of 10 seconds).
     * </pre>
     */
    public function object($string = '', $key = '') {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Returns the type of data pointed by a given key.
     *
     * @param   string  $key
     * @return  int
     *
     * Depending on the type of the data pointed by the key,
     * this method will return the following value:
     * - string: Redis::REDIS_STRING
     * - set:   Redis::REDIS_SET
     * - list:  Redis::REDIS_LIST
     * - zset:  Redis::REDIS_ZSET
     * - hash:  Redis::REDIS_HASH
     * - other: Redis::REDIS_NOT_FOUND
     * @link    http://redis.io/commands/type
     * @example $redis->type('key');
     */
    public function type($key) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Append specified string to the string stored in specified key.
     *
     * @param   string  $key
     * @param   string  $value
     * @return  int:    Size of the value after the append
     * @link    http://redis.io/commands/append
     * @example
     * <pre>
     * $redis->set('key', 'value1');
     * $redis->append('key', 'value2'); // 12
     * $redis->get('key');              // 'value1value2'
     * </pre>
     */
    public function append($key, $value) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }


    /**
     * Return a substring of a larger string
     *
     * @param   string  $key
     * @param   int     $start
     * @param   int     $end
     * @return  string: the substring
     * @link    http://redis.io/commands/getrange
     * @example
     * <pre>
     * $redis->set('key', 'string value');
     * $redis->getRange('key', 0, 5);   // 'string'
     * $redis->getRange('key', -5, -1); // 'value'
     * </pre>
     */
    public function getRange($key, $start, $end) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Return a substring of a larger string
     *
     * @deprecated
     * @param   string  $key
     * @param   int     $start
     * @param   int     $end
     */
    public function substr($key, $start, $end) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }


    /**
     * Changes a substring of a larger string.
     *
     * @param   string  $key
     * @param   int     $offset
     * @param   string  $value
     * @return  string: the length of the string after it was modified.
     * @link    http://redis.io/commands/setrange
     * @example
     * <pre>
     * $redis->set('key', 'Hello world');
     * $redis->setRange('key', 6, "redis"); // returns 11
     * $redis->get('key');                  // "Hello redis"
     * </pre>
     */
    public function setRange($key, $offset, $value) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Get the length of a string value.
     *
     * @param   string  $key
     * @return  int
     * @link    http://redis.io/commands/strlen
     * @example
     * <pre>
     * $redis->set('key', 'value');
     * $redis->strlen('key'); // 5
     * </pre>
     */
    public function strlen($key) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Return a single bit out of a larger string
     *
     * @param   string  $key
     * @param   int     $offset
     * @return  int:    the bit value (0 or 1)
     * @link    http://redis.io/commands/getbit
     * @example
     * <pre>
     * $redis->set('key', "\x7f");  // this is 0111 1111
     * $redis->getBit('key', 0);    // 0
     * $redis->getBit('key', 1);    // 1
     * </pre>
     */
    public function getBit($key, $offset) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Changes a single bit of a string.
     *
     * @param   string  $key
     * @param   int     $offset
     * @param   bool|int $value bool or int (1 or 0)
     * @return  int:    0 or 1, the value of the bit before it was set.
     * @link    http://redis.io/commands/setbit
     * @example
     * <pre>
     * $redis->set('key', "*");     // ord("*") = 42 = 0x2f = "0010 1010"
     * $redis->setBit('key', 5, 1); // returns 0
     * $redis->setBit('key', 7, 1); // returns 0
     * $redis->get('key');          // chr(0x2f) = "/" = b("0010 1111")
     * </pre>
     */
    public function setBit($key, $offset, $value) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Count bits in a string.
     *
     * @param   string  $key
     * @return  int     The number of bits set to 1 in the value behind the input key.
     * @link    http://redis.io/commands/bitcount
     * @example
     * <pre>
     * $redis->set('bit', '345'); // // 11 0011  0011 0100  0011 0101
     * var_dump( $redis->bitCount('bit', 0, 0) ); // int(4)
     * var_dump( $redis->bitCount('bit', 1, 1) ); // int(3)
     * var_dump( $redis->bitCount('bit', 2, 2) ); // int(4)
     * var_dump( $redis->bitCount('bit', 0, 2) ); // int(11)
     * </pre>
     */
    public function bitCount($key) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Sort
     *
     * @param   string  $key
     * @param   array   $option array(key => value, ...) - optional, with the following keys and values:
     * - 'by' => 'some_pattern_*',
     * - 'limit' => array(0, 1),
     * - 'get' => 'some_other_pattern_*' or an array of patterns,
     * - 'sort' => 'asc' or 'desc',
     * - 'alpha' => TRUE,
     * - 'store' => 'external-key'
     * @return  array
     * An array of values, or a number corresponding to the number of elements stored if that was used.
     * @link    http://redis.io/commands/sort
     * @example
     * <pre>
     * $redis->delete('s');
     * $redis->sadd('s', 5);
     * $redis->sadd('s', 4);
     * $redis->sadd('s', 2);
     * $redis->sadd('s', 1);
     * $redis->sadd('s', 3);
     *
     * var_dump($redis->sort('s')); // 1,2,3,4,5
     * var_dump($redis->sort('s', array('sort' => 'desc'))); // 5,4,3,2,1
     * var_dump($redis->sort('s', array('sort' => 'desc', 'store' => 'out'))); // (int)5
     * </pre>
     */
    public function sort($key, $option = null) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Returns the time to live left for a given key, in seconds. If the key doesn't exist, FALSE is returned.
     *
     * @param   string  $key
     * @return  int,    the time left to live in seconds.
     * @link    http://redis.io/commands/ttl
     * @example $redis->ttl('key');
     */
    public function ttl($key) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Returns a time to live left for a given key, in milliseconds.
     *
     * If the key doesn't exist, FALSE is returned.
     *
     * @param   string  $key
     * @return  int     the time left to live in milliseconds.
     * @link    http://redis.io/commands/pttl
     * @example $redis->pttl('key');
     */
    public function pttl($key) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Remove the expiration timer from a key.
     *
     * @param   string  $key
     * @return  bool:   TRUE if a timeout was removed, FALSE if the key didn’t exist or didn’t have an expiration timer.
     * @link    http://redis.io/commands/persist
     * @example $redis->persist('key');
     */
    public function persist($key) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Sets multiple key-value pairs in one atomic command.
     * MSETNX only returns TRUE if all the keys were set (see SETNX).
     *
     * @param   array(key => value) $array Pairs: array(key => value, ...)
     * @return  bool    TRUE in case of success, FALSE in case of failure.
     * @link    http://redis.io/commands/mset
     * @example
     * <pre>
     * $redis->mset(array('key0' => 'value0', 'key1' => 'value1'));
     * var_dump($redis->get('key0'));
     * var_dump($redis->get('key1'));
     * // Output:
     * // string(6) "value0"
     * // string(6) "value1"
     * </pre>
     */
    public function mset(array $array) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            throw new RedisException(__CLASS__ . "::" . __FUNCTION__ . " is not supported for multi-mode");
        }

        $result = true;
        $chunks = array();
        foreach ($array as $key=>$value) {
            $nodeNumber = $this->getNodeNumberByKey($key);
            if (isset($chunks[$nodeNumber])) {
                $chunks[$nodeNumber][$key] = $value;
            } else {
                $chunks[$nodeNumber] = array($key => $value);
            }
        }

        foreach ($chunks as $nodeNumber => $dataChunk) {
            if (!call_user_func_array(array($this->getNodeConnectionByNodeNumber($nodeNumber), __FUNCTION__), array($dataChunk))) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * @see mset()
     * @param   array $array
     * @link    http://redis.io/commands/msetnx
     */
    public function msetnx(array $array) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            throw new RedisException(__CLASS__ . "::" . __FUNCTION__ . " is not supported for multi-mode");
        }

        $result = true;
        $chunks = array();
        foreach ($array as $key=>$value) {
            $nodeNumber = $this->getNodeNumberByKey($key);
            if (isset($chunks[$nodeNumber])) {
                $chunks[$nodeNumber][$key] = $value;
            } else {
                $chunks[$nodeNumber] = array($key => $value);
            }
        }

        foreach ($chunks as $nodeNumber => $dataChunk) {
            if (!call_user_func_array(array($this->getNodeConnectionByNodeNumber($nodeNumber), __FUNCTION__), array($dataChunk))) {
                $result = false;
            }
        }

        return $result;
    }


    /**
     * Returns the values of all specified keys.
     *
     * For every key that does not hold a string value or does not exist,
     * the special value false is returned. Because of this, the operation never fails.
     *
     * @param array $array
     * @return array
     * @link http://redis.io/commands/mget
     * @example
     * <pre>
     * $redis->delete('x', 'y', 'z', 'h');    // remove x y z
     * $redis->mset(array('x' => 'a', 'y' => 'b', 'z' => 'c'));
     * $redis->hset('h', 'field', 'value');
     * var_dump($redis->mget(array('x', 'y', 'z', 'h')));
     * // Output:
     * // array(3) {
     * // [0]=>
     * // string(1) "a"
     * // [1]=>
     * // string(1) "b"
     * // [2]=>
     * // string(1) "c"
     * // [3]=>
     * // bool(false)
     * // }
     * </pre>
     */
    public function mget(array $array) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            throw new RedisException(__CLASS__ . "::" . __FUNCTION__ . " is not supported for multi-mode");
        }

        $result = array();
        $chunks = array();
        foreach ($array as $key) {
            $result[$key] = false;

            $nodeNumber = $this->getNodeNumberByKey($key);
            if (isset($chunks[$nodeNumber])) {
                $chunks[$nodeNumber][] = $key;
            } else {
                $chunks[$nodeNumber] = array($key);
            }
        }

        foreach ($chunks as $nodeNumber=>$keys) {
            $nodeResult = call_user_func_array(array($this->getNodeConnectionByNodeNumber($nodeNumber), __FUNCTION__), array($keys));
            foreach ($keys as $index=>$key) {
                $result[$key] = $nodeResult[$index];
            }
        }

        return array_values($result);
    }

    /**
     * Get the values of all the specified keys. If one or more keys dont exist, the array will contain FALSE at the
     * position of the key.
     *
     * @param   array $keys Array containing the list of the keys
     * @return  array Array containing the values related to keys in argument
     * @example
     * <pre>
     * $redis->set('key1', 'value1');
     * $redis->set('key2', 'value2');
     * $redis->set('key3', 'value3');
     * $redis->getMultiple(array('key1', 'key2', 'key3')); // array('value1', 'value2', 'value3');
     * $redis->getMultiple(array('key0', 'key1', 'key5')); // array(`FALSE`, 'value2', `FALSE`);
     * </pre>
     */
    public function getMultiple(array $keys) {
        return call_user_func_array(array($this, "mget"), func_get_args());
    }

    /**
     * Adds the specified member with a given score to the sorted set stored at key.
     *
     * @param   string  $key    Required key
     * @param   float   $score1 Required score
     * @param   string  $value1 Required value
     * @param   float   $score2 Optional score
     * @param   string  $value2 Optional value
     * @param   float   $scoreN Optional score
     * @param   string  $valueN Optional value
     * @return  int     Number of values added
     * @link    http://redis.io/commands/zadd
     * @example
     * <pre>
     * <pre>
     * $redis->zAdd('z', 1, 'v2', 2, 'v2', 3, 'v3', 4, 'v4' );  // int(2)
     * $redis->zRem('z', 'v2', 'v3');                           // int(2)
     * var_dump( $redis->zRange('z', 0, -1) );
     * //// Output:
     * // array(2) {
     * //   [0]=> string(2) "v1"
     * //   [1]=> string(2) "v4"
     * // }
     * </pre>
     * </pre>
     */
    public function zAdd($key, $score1, $value1, $score2 = null, $value2 = null, $scoreN = null, $valueN = null) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Returns a range of elements from the ordered set stored at the specified key,
     * with values in the range [start, end]. start and stop are interpreted as zero-based indices:
     * 0 the first element,
     * 1 the second ...
     * -1 the last element,
     * -2 the penultimate ...
     *
     * @param   string  $key
     * @param   int     $start
     * @param   int     $end
     * @param   bool    $withscores
     * @return  array   Array containing the values in specified range.
     * @link    http://redis.io/commands/zrange
     * @example
     * <pre>
     * $redis->zAdd('key1', 0, 'val0');
     * $redis->zAdd('key1', 2, 'val2');
     * $redis->zAdd('key1', 10, 'val10');
     * $redis->zRange('key1', 0, -1); // array('val0', 'val2', 'val10')
     * // with scores
     * $redis->zRange('key1', 0, -1, true); // array('val0' => 0, 'val2' => 2, 'val10' => 10)
     * </pre>
     */
    public function zRange($key, $start, $end, $withscores = null) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Deletes a specified member from the ordered set.
     *
     * @param   string  $key
     * @param   string  $member1
     * @param   string  $member2
     * @param   string  $memberN
     * @return  int     Number of deleted values
     * @link    http://redis.io/commands/zrem
     * @example
     * <pre>
     * $redis->zAdd('z', 1, 'v2', 2, 'v2', 3, 'v3', 4, 'v4' );  // int(2)
     * $redis->zRem('z', 'v2', 'v3');                           // int(2)
     * var_dump( $redis->zRange('z', 0, -1) );
     * //// Output:
     * // array(2) {
     * //   [0]=> string(2) "v1"
     * //   [1]=> string(2) "v4"
     * // }
     * </pre>
     */
    public function zRem($key, $member1, $member2 = null, $memberN = null) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * @see zRem()
     * @param   string  $key
     * @param   string  $member1
     * @param   string  $member2
     * @param   string  $memberN
     * @link    http://redis.io/commands/zrem
     */
    public function zDelete($key, $member1, $member2 = null, $memberN = null) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Returns the elements of the sorted set stored at the specified key in the range [start, end]
     * in reverse order. start and stop are interpretated as zero-based indices:
     * 0 the first element,
     * 1 the second ...
     * -1 the last element,
     * -2 the penultimate ...
     *
     * @param   string  $key
     * @param   int     $start
     * @param   int     $end
     * @param   bool    $withscore
     * @return  array   Array containing the values in specified range.
     * @link    http://redis.io/commands/zrevrange
     * @example
     * <pre>
     * $redis->zAdd('key', 0, 'val0');
     * $redis->zAdd('key', 2, 'val2');
     * $redis->zAdd('key', 10, 'val10');
     * $redis->zRevRange('key', 0, -1); // array('val10', 'val2', 'val0')
     *
     * // with scores
     * $redis->zRevRange('key', 0, -1, true); // array('val10' => 10, 'val2' => 2, 'val0' => 0)
     * </pre>
     */
    public function zRevRange($key, $start, $end, $withscore = null) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Returns the elements of the sorted set stored at the specified key which have scores in the
     * range [start,end]. Adding a parenthesis before start or end excludes it from the range.
     * +inf and -inf are also valid limits.
     *
     * zRevRangeByScore returns the same items in reverse order, when the start and end parameters are swapped.
     *
     * @param   string  $key
     * @param   int     $start
     * @param   int     $end
     * @param   array   $options Two options are available:
     *                      - withscores => TRUE,
     *                      - and limit => array($offset, $count)
     * @return  array   Array containing the values in specified range.
     * @link    http://redis.io/commands/zrangebyscore
     * @example
     * <pre>
     * $redis->zAdd('key', 0, 'val0');
     * $redis->zAdd('key', 2, 'val2');
     * $redis->zAdd('key', 10, 'val10');
     * $redis->zRangeByScore('key', 0, 3);                                          // array('val0', 'val2')
     * $redis->zRangeByScore('key', 0, 3, array('withscores' => TRUE);              // array('val0' => 0, 'val2' => 2)
     * $redis->zRangeByScore('key', 0, 3, array('limit' => array(1, 1));                        // array('val2' => 2)
     * $redis->zRangeByScore('key', 0, 3, array('limit' => array(1, 1));                        // array('val2')
     * $redis->zRangeByScore('key', 0, 3, array('withscores' => TRUE, 'limit' => array(1, 1));  // array('val2' => 2)
     * </pre>
     */
    public function zRangeByScore($key, $start, $end, array $options = array()) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * @see zRangeByScore()
     * @param   string  $key
     * @param   int     $start
     * @param   int     $end
     * @param   array   $options
     *
     * @return     array
     */
    public function zRevRangeByScore($key, $start, $end, array $options = array()) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Returns the number of elements of the sorted set stored at the specified key which have
     * scores in the range [start,end]. Adding a parenthesis before start or end excludes it
     * from the range. +inf and -inf are also valid limits.
     *
     * @param   string  $key
     * @param   string  $start
     * @param   string  $end
     * @return  int     the size of a corresponding zRangeByScore.
     * @link    http://redis.io/commands/zcount
     * @example
     * <pre>
     * $redis->zAdd('key', 0, 'val0');
     * $redis->zAdd('key', 2, 'val2');
     * $redis->zAdd('key', 10, 'val10');
     * $redis->zCount('key', 0, 3); // 2, corresponding to array('val0', 'val2')
     * </pre>
     */
    public function zCount($key, $start, $end) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Deletes the elements of the sorted set stored at the specified key which have scores in the range [start,end].
     *
     * @param   string          $key
     * @param   float|string    $start double or "+inf" or "-inf" string
     * @param   float|string    $end double or "+inf" or "-inf" string
     * @return  int             The number of values deleted from the sorted set
     * @link    http://redis.io/commands/zremrangebyscore
     * @example
     * <pre>
     * $redis->zAdd('key', 0, 'val0');
     * $redis->zAdd('key', 2, 'val2');
     * $redis->zAdd('key', 10, 'val10');
     * $redis->zRemRangeByScore('key', 0, 3); // 2
     * </pre>
     */
    public function zRemRangeByScore($key, $start, $end) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * @see zRemRangeByScore()
     * @param string    $key
     * @param float     $start
     * @param float     $end
     */
    public function zDeleteRangeByScore($key, $start, $end) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Deletes the elements of the sorted set stored at the specified key which have rank in the range [start,end].
     *
     * @param   string  $key
     * @param   int     $start
     * @param   int     $end
     * @return  int     The number of values deleted from the sorted set
     * @link    http://redis.io/commands/zremrangebyrank
     * @example
     * <pre>
     * $redis->zAdd('key', 1, 'one');
     * $redis->zAdd('key', 2, 'two');
     * $redis->zAdd('key', 3, 'three');
     * $redis->zRemRangeByRank('key', 0, 1); // 2
     * $redis->zRange('key', 0, -1, array('withscores' => TRUE)); // array('three' => 3)
     * </pre>
     */
    public function zRemRangeByRank($key, $start, $end) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * @see zRemRangeByRank()
     * @param   string  $key
     * @param   int     $start
     * @param   int     $end
     * @link    http://redis.io/commands/zremrangebyscore
     */
    public function zDeleteRangeByRank($key, $start, $end) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Returns the cardinality of an ordered set.
     *
     * @param   string  $key
     * @return  int     the set's cardinality
     * @link    http://redis.io/commands/zsize
     * @example
     * <pre>
     * $redis->zAdd('key', 0, 'val0');
     * $redis->zAdd('key', 2, 'val2');
     * $redis->zAdd('key', 10, 'val10');
     * $redis->zCard('key');            // 3
     * </pre>
     */
    public function zCard($key) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * @see zCard()
     * @param string $key
     */
    public function zSize($key) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Returns the score of a given member in the specified sorted set.
     *
     * @param   string  $key
     * @param   string  $member
     * @return  float
     * @link    http://redis.io/commands/zscore
     * @example
     * <pre>
     * $redis->zAdd('key', 2.5, 'val2');
     * $redis->zScore('key', 'val2'); // 2.5
     * </pre>
     */
    public function zScore($key, $member) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Returns the rank of a given member in the specified sorted set, starting at 0 for the item
     * with the smallest score. zRevRank starts at 0 for the item with the largest score.
     *
     * @param   string  $key
     * @param   string  $member
     * @return  int     the item's score.
     * @link    http://redis.io/commands/zrank
     * @example
     * <pre>
     * $redis->delete('z');
     * $redis->zAdd('key', 1, 'one');
     * $redis->zAdd('key', 2, 'two');
     * $redis->zRank('key', 'one');     // 0
     * $redis->zRank('key', 'two');     // 1
     * $redis->zRevRank('key', 'one');  // 1
     * $redis->zRevRank('key', 'two');  // 0
     * </pre>
     */
    public function zRank($key, $member) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * @see zRank()
     * @param string $key
     * @param string $member
     */
    public function zRevRank($key, $member) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Increments the score of a member from a sorted set by a given amount.
     *
     * @param   string  $key
     * @param   float   $value (double) value that will be added to the member's score
     * @param   string  $member
     * @return  float   the new value
     * @link    http://redis.io/commands/zincrby
     * @example
     * <pre>
     * $redis->delete('key');
     * $redis->zIncrBy('key', 2.5, 'member1');  // key or member1 didn't exist, so member1's score is to 0
     *                                          // before the increment and now has the value 2.5
     * $redis->zIncrBy('key', 1, 'member1');    // 3.5
     * </pre>
     */
    public function zIncrBy($key, $value, $member) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Adds a value to the hash stored at key. If this value is already in the hash, FALSE is returned.
     *
     * @param string $key
     * @param string $hashKey
     * @param string $value
     * @return int
     * 1 if value didn't exist and was added successfully,
     * 0 if the value was already present and was replaced, FALSE if there was an error.
     * @link    http://redis.io/commands/hset
     * @example
     * <pre>
     * $redis->delete('h')
     * $redis->hSet('h', 'key1', 'hello');  // 1, 'key1' => 'hello' in the hash at "h"
     * $redis->hGet('h', 'key1');           // returns "hello"
     *
     * $redis->hSet('h', 'key1', 'plop');   // 0, value was replaced.
     * $redis->hGet('h', 'key1');           // returns "plop"
     * </pre>
     */
    public function hSet($key, $hashKey, $value) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Adds a value to the hash stored at key only if this field isn't already in the hash.
     *
     * @param   string  $key
     * @param   string  $hashKey
     * @param   string  $value
     * @return  bool    TRUE if the field was set, FALSE if it was already present.
     * @link    http://redis.io/commands/hsetnx
     * @example
     * <pre>
     * $redis->delete('h')
     * $redis->hSetNx('h', 'key1', 'hello'); // TRUE, 'key1' => 'hello' in the hash at "h"
     * $redis->hSetNx('h', 'key1', 'world'); // FALSE, 'key1' => 'hello' in the hash at "h". No change since the field
     * wasn't replaced.
     * </pre>
     */
    public function hSetNx($key, $hashKey, $value) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Gets a value from the hash stored at key.
     * If the hash table doesn't exist, or the key doesn't exist, FALSE is returned.
     *
     * @param   string  $key
     * @param   string  $hashKey
     * @return  string  The value, if the command executed successfully BOOL FALSE in case of failure
     * @link    http://redis.io/commands/hget
     */
    public function hGet($key, $hashKey) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Returns the length of a hash, in number of items
     *
     * @param   string  $key
     * @return  int     the number of items in a hash, FALSE if the key doesn't exist or isn't a hash.
     * @link    http://redis.io/commands/hlen
     * @example
     * <pre>
     * $redis->delete('h')
     * $redis->hSet('h', 'key1', 'hello');
     * $redis->hSet('h', 'key2', 'plop');
     * $redis->hLen('h'); // returns 2
     * </pre>
     */
    public function hLen($key) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Removes a values from the hash stored at key.
     * If the hash table doesn't exist, or the key doesn't exist, FALSE is returned.
     *
     * @param   string  $key
     * @param   string  $hashKey1
     * @param   string  $hashKey2
     * @param   string  $hashKeyN
     * @return  int     Number of deleted fields
     * @link    http://redis.io/commands/hdel
     * @example
     * <pre>
     * $redis->hMSet('h',
     *               array(
     *                    'f1' => 'v1',
     *                    'f2' => 'v2',
     *                    'f3' => 'v3',
     *                    'f4' => 'v4',
     *               ));
     *
     * var_dump( $redis->hDel('h', 'f1') );        // int(1)
     * var_dump( $redis->hDel('h', 'f2', 'f3') );  // int(2)
     * s
     * var_dump( $redis->hGetAll('h') );
     * //// Output:
     * //  array(1) {
     * //    ["f4"]=> string(2) "v4"
     * //  }
     * </pre>
     */
    public function hDel($key, $hashKey1, $hashKey2 = null, $hashKeyN = null) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Returns the keys in a hash, as an array of strings.
     *
     * @param   string  $key
     * @return  array   An array of elements, the keys of the hash. This works like PHP's array_keys().
     * @link    http://redis.io/commands/hkeys
     * @example
     * <pre>
     * $redis->delete('h');
     * $redis->hSet('h', 'a', 'x');
     * $redis->hSet('h', 'b', 'y');
     * $redis->hSet('h', 'c', 'z');
     * $redis->hSet('h', 'd', 't');
     * var_dump($redis->hKeys('h'));
     *
     * // Output:
     * // array(4) {
     * // [0]=>
     * // string(1) "a"
     * // [1]=>
     * // string(1) "b"
     * // [2]=>
     * // string(1) "c"
     * // [3]=>
     * // string(1) "d"
     * // }
     * // The order is random and corresponds to redis' own internal representation of the set structure.
     * </pre>
     */
    public function hKeys($key) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Returns the values in a hash, as an array of strings.
     *
     * @param   string  $key
     * @return  array   An array of elements, the values of the hash. This works like PHP's array_values().
     * @link    http://redis.io/commands/hvals
     * @example
     * <pre>
     * $redis->delete('h');
     * $redis->hSet('h', 'a', 'x');
     * $redis->hSet('h', 'b', 'y');
     * $redis->hSet('h', 'c', 'z');
     * $redis->hSet('h', 'd', 't');
     * var_dump($redis->hVals('h'));
     *
     * // Output
     * // array(4) {
     * //   [0]=>
     * //   string(1) "x"
     * //   [1]=>
     * //   string(1) "y"
     * //   [2]=>
     * //   string(1) "z"
     * //   [3]=>
     * //   string(1) "t"
     * // }
     * // The order is random and corresponds to redis' own internal representation of the set structure.
     * </pre>
     */
    public function hVals($key) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Returns the whole hash, as an array of strings indexed by strings.
     *
     * @param   string  $key
     * @return  array   An array of elements, the contents of the hash.
     * @link    http://redis.io/commands/hgetall
     * @example
     * <pre>
     * $redis->delete('h');
     * $redis->hSet('h', 'a', 'x');
     * $redis->hSet('h', 'b', 'y');
     * $redis->hSet('h', 'c', 'z');
     * $redis->hSet('h', 'd', 't');
     * var_dump($redis->hGetAll('h'));
     *
     * // Output:
     * // array(4) {
     * //   ["a"]=>
     * //   string(1) "x"
     * //   ["b"]=>
     * //   string(1) "y"
     * //   ["c"]=>
     * //   string(1) "z"
     * //   ["d"]=>
     * //   string(1) "t"
     * // }
     * // The order is random and corresponds to redis' own internal representation of the set structure.
     * </pre>
     */
    public function hGetAll($key) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Verify if the specified member exists in a key.
     *
     * @param   string  $key
     * @param   string  $hashKey
     * @return  bool:   If the member exists in the hash table, return TRUE, otherwise return FALSE.
     * @link    http://redis.io/commands/hexists
     * @example
     * <pre>
     * $redis->hSet('h', 'a', 'x');
     * $redis->hExists('h', 'a');               //  TRUE
     * $redis->hExists('h', 'NonExistingKey');  // FALSE
     * </pre>
     */
    public function hExists($key, $hashKey) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Increments the value of a member from a hash by a given amount.
     *
     * @param   string  $key
     * @param   string  $hashKey
     * @param   int     $value (integer) value that will be added to the member's value
     * @return  int     the new value
     * @link    http://redis.io/commands/hincrby
     * @example
     * <pre>
     * $redis->delete('h');
     * $redis->hIncrBy('h', 'x', 2); // returns 2: h[x] = 2 now.
     * $redis->hIncrBy('h', 'x', 1); // h[x] ← 2 + 1. Returns 3
     * </pre>
     */
    public function hIncrBy($key, $hashKey, $value) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Increment the float value of a hash field by the given amount
     * @param   string  $key
     * @param   string  $field
     * @param   float   $increment
     * @return  float
     * @link    http://redis.io/commands/hincrbyfloat
     * @example
     * <pre>
     * $redis = new Redis();
     * $redis->connect('127.0.0.1');
     * $redis->hset('h', 'float', 3);
     * $redis->hset('h', 'int',   3);
     * var_dump( $redis->hIncrByFloat('h', 'float', 1.5) ); // float(4.5)
     *
     * var_dump( $redis->hGetAll('h') );
     *
     * // Output
     *  array(2) {
     *    ["float"]=>
     *    string(3) "4.5"
     *    ["int"]=>
     *    string(1) "3"
     *  }
     * </pre>
     */
    public function hIncrByFloat($key, $field, $increment) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Fills in a whole hash. Non-string values are converted to string, using the standard (string) cast.
     * NULL values are stored as empty strings
     *
     * @param   string  $key
     * @param   array   $hashKeys key → value array
     * @return  bool
     * @link    http://redis.io/commands/hmset
     * @example
     * <pre>
     * $redis->delete('user:1');
     * $redis->hMset('user:1', array('name' => 'Joe', 'salary' => 2000));
     * $redis->hIncrBy('user:1', 'salary', 100); // Joe earns 100 more now.
     * </pre>
     */
    public function hMset($key, $hashKeys) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Retirieve the values associated to the specified fields in the hash.
     *
     * @param   string  $key
     * @param   array   $hashKeys
     * @return  array   Array An array of elements, the values of the specified fields in the hash,
     * with the hash keys as array keys.
     * @link    http://redis.io/commands/hmget
     * @example
     * <pre>
     * $redis->delete('h');
     * $redis->hSet('h', 'field1', 'value1');
     * $redis->hSet('h', 'field2', 'value2');
     * $redis->hmGet('h', array('field1', 'field2')); // returns array('field1' => 'value1', 'field2' => 'value2')
     * </pre>
     */
    public function hMGet($key, $hashKeys) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            $this->multiQueue[] = array(
                'function' => __FUNCTION__,
                'args'     => func_get_args(),
                'key'      => $key,
            );

            return $this;
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Dump a key out of a redis database, the value of which can later be passed into redis using the RESTORE command.
     * The data that comes out of DUMP is a binary representation of the key as Redis stores it.
     * @param   string  $key
     * @return  string  The Redis encoded value of the key, or FALSE if the key doesn't exist
     * @link    http://redis.io/commands/dump
     * @example
     * <pre>
     * $redis->set('foo', 'bar');
     * $val = $redis->dump('foo'); // $val will be the Redis encoded key value
     * </pre>
     */
    public function dump($key) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            throw new RedisException(__CLASS__ . "::" . __FUNCTION__ . " is not supported for multi-mode");
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Restore a key from the result of a DUMP operation.
     *
     * @param   string  $key    The key name
     * @param   int     $ttl    How long the key should live (if zero, no expire will be set on the key)
     * @param   string  $value  (binary).  The Redis encoded key value (from DUMP)
     * @return  bool
     * @link    http://redis.io/commands/restore
     * @example
     * <pre>
     * $redis->set('foo', 'bar');
     * $val = $redis->dump('foo');
     * $redis->restore('bar', 0, $val); // The key 'bar', will now be equal to the key 'foo'
     * </pre>
     */
    public function restore($key, $ttl, $value) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            throw new RedisException(__CLASS__ . "::" . __FUNCTION__ . " is not supported for multi-mode");
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }

    /**
     * Migrates a key to a different Redis instance.
     *
     * @param   string  $host       The destination host
     * @param   int     $port       The TCP port to connect to.
     * @param   string  $key        The key to migrate.
     * @param   int     $db         The target DB.
     * @param   int     $timeout    The maximum amount of time given to this transfer.
     * @return  bool
     * @link    http://redis.io/commands/migrate
     * @example
     * <pre>
     * $redis->migrate('backup', 6379, 'foo', 0, 3600);
     * </pre>
     */
    public function migrate($host, $port, $key, $db, $timeout) {
        $startTime = microtime(true);

        if ($this->mode == self::MULTI_MODE) {
            throw new RedisException(__CLASS__ . "::" . __FUNCTION__ . " is not supported for multi-mode");
        }

        $ret = call_user_func_array(array($this->getNodeConnectionByKey($key), __FUNCTION__), func_get_args());
        
        $this->metrics['requests'][] = array(
            'method'  => __FUNCTION__,
            'args'  => func_get_args(),
            'timeout' => $timeout = microtime(true) - $startTime,
        );
        
        $this->metrics['timeout']+=$timeout;

        return $ret;
    }
}

/**
 * RedisException
 *
 * @package RedisBundle
 */
class RedisException extends \Exception {

}