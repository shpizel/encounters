<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\CronScript;


/**
 * RedisToLeveldbMigrationCommand
 *
 * @package EncountersBundle
 */
class RedisToLeveldbMigrationCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Redis to leveldb migration script",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "leveldb:migrate"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        //$this->migrateBatteries();
        //$this->migrateEnergies();
        //$this->migrateVariables();
        $this->migrateCounters();
    }

    protected function migrateBatteries() {
        $counter = 0;

        $redisNodes = $this->getRedis()->getNodes();
        $ldb = $this->getLeveldb();
        foreach ($redisNodes as $redisNode) {
            $this->log((string) $redisNode, 64);
            $host = $redisNode->getHost();
            $port = $redisNode->getPort();
            $database = $redisNode->getDatabase();

            $nodeConnection = $this->getRedis()->getNodeConnection($redisNode);

            $batteryKeys = `redis-cli -h $host -p $port -n $database keys 'battery_by_*'`;
            if ($batteryKeys = trim($batteryKeys)) {
                $batteryKeys = explode("\n", $batteryKeys);
                foreach ($batteryKeys as $n => $batteryKey) {
                    $batteryKeys[$n] = $batteryKey = trim($batteryKey);
                }

                $batteryKeys = array_chunk($batteryKeys, 1000);
                foreach ($batteryKeys as $keys) {
                    $data = $nodeConnection->mget($keys);

                    $ldata = array();
                    foreach ($keys as $kn => $key) {
                        if (preg_match("!battery_by_(\d+)!", $key, $userId)) {
                            $userId = (int) array_pop($userId);
                            $battery = (int) $data[$kn];

                            $ldata["encounters:battery:{$userId}"] = $battery;
                            $counter++;
                        }
                    }

                    if ($ldata) {
                        $ldb->set($ldata);
                        $ldata = array();

                        $ldb->execute();

                        $this->log($counter, -1);
                    }
                }
            }
        }
    }

    protected function migrateEnergies() {
        $counter = 0;

        $redisNodes = $this->getRedis()->getNodes();
        $ldb = $this->getLeveldb();
        foreach ($redisNodes as $redisNode) {
            $this->log((string) $redisNode, 64);
            $host = $redisNode->getHost();
            $port = $redisNode->getPort();
            $database = $redisNode->getDatabase();

            $nodeConnection = $this->getRedis()->getNodeConnection($redisNode);

            $batteryKeys = `redis-cli -h $host -p $port -n $database keys 'energy_by_*'`;
            if ($batteryKeys = trim($batteryKeys)) {
                $batteryKeys = explode("\n", $batteryKeys);
                foreach ($batteryKeys as $n => $batteryKey) {
                    $batteryKeys[$n] = $batteryKey = trim($batteryKey);
                }

                $batteryKeys = array_chunk($batteryKeys, 1000);
                foreach ($batteryKeys as $keys) {
                    $data = $nodeConnection->mget($keys);

                    $ldata = array();
                    foreach ($keys as $kn => $key) {
                        if (preg_match("!energy_by_(\d+)!", $key, $userId)) {
                            $userId = (int) array_pop($userId);
                            $energy = (int) $data[$kn];

                            $ldata["encounters:energy:{$userId}"] = $energy;
                            $counter++;
                        }
                    }

                    if ($ldata) {
                        $ldb->set($ldata);
                        $ldata = array();

                        $ldb->execute();

                        $this->log($counter, -1);
                    }
                }
            }
        }
    }

    protected function migrateVariables() {
        $counter = 0;

        $redisNodes = $this->getRedis()->getNodes();
        $ldb = $this->getLeveldb();
        foreach ($redisNodes as $redisNode) {
            $this->log((string) $redisNode, 64);
            $host = $redisNode->getHost();
            $port = $redisNode->getPort();
            $database = $redisNode->getDatabase();

            $nodeConnection = $this->getRedis()->getNodeConnection($redisNode);

            $batteryKeys = `redis-cli -h $host -p $port -n $database keys 'variables_by_*'`;
            if ($batteryKeys = trim($batteryKeys)) {
                $batteryKeys = explode("\n", $batteryKeys);
                foreach ($batteryKeys as $n => $batteryKey) {
                    $batteryKeys[$n] = $batteryKey = trim($batteryKey);
                }

                $batteryKeys = array_chunk($batteryKeys, 1000);
                foreach ($batteryKeys as $keys) {

                    $nodeConnection->multi();
                    foreach ($keys as $_____key) {
                        $nodeConnection->hGetAll($_____key);
                    }

                    $__data = $nodeConnection->exec();
                    $data = array();
                    foreach ($keys as $_____n=>$_____key) {
                        $data[$_____key] = $__data[$_____n];
                    }

                    $ldata = array();
                    foreach ($keys as $kn => $key) {
                        if (preg_match("!variables_by_(\d+)!", $key, $userId)) {
                            $userId = (int) array_pop($userId);
                            $hdata = $__data[$kn];

                            foreach ($hdata as $hkey=>$hval) {
                                $ldata["encounters:variables:{$userId}:{$hkey}"] = $hval;
                            }

                            $counter++;
                        }
                    }

                    if ($ldata) {
                        $ldb->set($ldata);
                        $ldata = array();

                        $ldb->execute();

                        $this->log($counter, -1);
                    }
                }
            }
        }
    }

    protected function migrateCounters() {
        $counter = 0;

        $redisNodes = $this->getRedis()->getNodes();
        $ldb = $this->getLeveldb();
        foreach ($redisNodes as $redisNode) {
            $this->log((string) $redisNode, 64);
            $host = $redisNode->getHost();
            $port = $redisNode->getPort();
            $database = $redisNode->getDatabase();

            $nodeConnection = $this->getRedis()->getNodeConnection($redisNode);

            $batteryKeys = `redis-cli -h $host -p $port -n $database keys 'counters_by_*'`;
            if ($batteryKeys = trim($batteryKeys)) {
                $batteryKeys = explode("\n", $batteryKeys);
                foreach ($batteryKeys as $n => $batteryKey) {
                    $batteryKeys[$n] = $batteryKey = trim($batteryKey);
                }

                $batteryKeys = array_chunk($batteryKeys, 1000);
                foreach ($batteryKeys as $keys) {

                    $nodeConnection->multi();
                    foreach ($keys as $_____key) {
                        $nodeConnection->hGetAll($_____key);
                    }

                    $__data = $nodeConnection->exec();
                    $data = array();
                    foreach ($keys as $_____n=>$_____key) {
                        $data[$_____key] = $__data[$_____n];
                    }

                    $ldata = array();
                    foreach ($keys as $kn => $key) {
                        if (preg_match("!counters_by_(\d+)!", $key, $userId)) {
                            $userId = (int) array_pop($userId);
                            $hdata = $__data[$kn];

                            foreach ($hdata as $hkey=>$hval) {
                                $ldata["encounters:counters:{$userId}:{$hkey}"] = $hval;
                            }

                            $counter++;
                        }
                    }

                    if ($ldata) {
                        $ldb->set($ldata);
                        $ldata = array();

                        $ldb->execute();

                        $this->log($counter, -1);
                    }
                }
            }
        }
    }
}