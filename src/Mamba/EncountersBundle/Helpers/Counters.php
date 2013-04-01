<?php
namespace Mamba\EncountersBundle\Helpers;

use Core\RedisBundle\Redis;

/**
 * Counters
 *
 * @package EncountersBundle
 */
class Counters extends Helper {

    const

        /**
         * Ключ для хранения счетчиков
         *
         * @var str
         */
        LEVELDB_USER_COUNTERS_KEY = "encounters:counters:%d:%s"
    ;

    /**
     * Counter getter
     *
     * @param int $userId
     * @param string $key
     * @return mixed
     */
    public function get($userId, $key) {
        if (!is_int($userId)) {
            throw new CountersException("Invalid user id: \n" . var_export($userId, true));
        }

        $LevelDb = $this->getLeveldb();
        $Request = $LevelDb->get(
            $leveldbKey = sprintf(self::LEVELDB_USER_COUNTERS_KEY, $userId, $key)
        );
        $LevelDb->execute();

        if (($result = $Request->getResult()) && isset($result[$leveldbKey])) {
            return $result[$leveldbKey];
        }
    }

    /**
     * Counters multi getter
     *
     * @param array $users
     * @param array $counters
     * @return mixed
     * @throws CountersException
     */
    public function getMulti(array $users, array $counters) {
        foreach ($users as $userId) {
            if (!is_int($userId)) {
                throw new CountersException("Invalid user id: \n" . var_export($userId, true));
            }
        }

        $keys = [];
        foreach ($users as $userId) {
            foreach ($counters as $counterName) {
                $leveldbKey = sprintf(self::LEVELDB_USER_COUNTERS_KEY, $userId, $counterName);
                $keys[] = $leveldbKey;
            }
        }

        if (!$keys) return;

        $LevelDb = $this->getLeveldb();
        $Request = $LevelDb->get($keys);
        $LevelDb->execute();

        $regexp = "!" . str_replace(array("%d", "%s"), array("(\\d+)", "(.*)$"), self::LEVELDB_USER_COUNTERS_KEY) . "!S";

        if ($results = $Request->getResult()) {
            $ret = [];

            foreach ($results as $key=>$result) {
                if (preg_match($regexp, $key, $data)) {
                    $leveldbKey = array_pop($data);
                    $userId = array_pop($data);

                    if ($leveldbKey && $userId) {
                        if (!isset($ret[$userId])) {
                            $ret[$userId] = [];
                        }

                        $ret[$userId][$leveldbKey] = (int) $result;
                    }
                }
            }

            foreach ($ret as $userId=>$userCounters) {
                foreach ($counters as $counter) {
                    if (!isset($userCounters[$counter])) {
                        $ret[$userId][$counter] = null;
                    }
                }
            }

            return $ret;
        }
    }



    /**
     * All counter getter
     *
     * @param int $userId
     * @return mixed
     */
    public function getAll($userId) {
        if (!is_int($userId)) {
            throw new CountersException("Invalid user id: \n" . var_export($userId, true));
        }

        $LevelDb = $this->getLeveldb();
        $Request = $LevelDb->get_range(
            $leveldbKey = sprintf(self::LEVELDB_USER_COUNTERS_KEY, $userId, ''),
            null,
            100
        );

        $LevelDb->execute();

        if ($result = $Request->getResult()) {
            foreach ($result as $key=>$val) {
                if (strpos($key, $leveldbKey) === false) {
                    unset($result[$key]);
                }
            }

            return $result;
        }
    }

    /**
     * Counter setter
     *
     * @param int $userId
     * @param string $key
     * @param int $value
     */
    public function set($userId, $key, $value) {
        if (!is_int($userId)) {
            throw new CountersException("Invalid user id: \n" . var_export($userId, true));
        }

        if (!is_int($value)) {
            throw new CountersException("Invalid value: \n" . var_export($value, true));
        }

        $LevelDb = $this->getLeveldb();
        $Request = $LevelDb->set(array(
            $leveldbKey = sprintf(self::LEVELDB_USER_COUNTERS_KEY, $userId, $key) => $value
        ));
        $LevelDb->execute();

        if ($Request->getResult() === true) {
            return true;
        }

        //return $this->getRedis()->hSet(sprintf(self::REDIS_HASH_USER_COUNTERS_KEY, $userId), $key, $value);
    }

    /**
     * Counter increment
     *
     * @param int $userId
     * @param string $key
     * @param int $rate
     */
    public function incr($userId, $key, $rate = 1) {
        if (!is_int($userId)) {
            throw new CountersException("Invalid user id: \n" . var_export($userId, true));
        }

        if (!is_int($rate)) {
            throw new CountersException("Invalid rate: \n" . var_export($rate, true));
        }

        $LevelDb = $this->getLeveldb();
        $Request = $LevelDb->inc(array(
            $leveldbKey = sprintf(self::LEVELDB_USER_COUNTERS_KEY, $userId, $key) => $rate
        ));
        $LevelDb->execute();

        if (($result = $Request->getResult()) && isset($result[$leveldbKey])) {
            return $result[$leveldbKey];
        }
    }

    /**
     * Counter decrement
     *
     * @param int $userId
     * @param string $key
     * @param int $rate
     */
    public function decr($userId, $key, $rate = 1) {
        if (!is_int($userId)) {
            throw new CountersException("Invalid user id: \n" . var_export($userId, true));
        }

        if (!is_int($rate)) {
            throw new CountersException("Invalid rate: \n" . var_export($rate, true));
        }

        return $this->incr($userId, $key, -$rate);
    }
}

/**
 * CountersException
 *
 * @package EncountersBundle
 */
class CountersException extends \Exception {

}