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
        $this->migrateBatteries();
    }

    protected function migrateBatteries() {
        $redisNodes = $this->getRedis()->getNodes();
        $ldb = $this->getLeveldb();
        foreach ($redisNodes as $redisNode) {
            $host = $redisNode->getHost();
            $port = $redisNode->getPort();
            $database = $redisNode->getDatabase();

            $nodeConnection = $this->getRedis()->getNodeConnection($redisNode);

            $batteryKeys = `redis-cli -h $host -p $port -n $database keys 'battery_by_*'`;
            if ($batteryKeys = trim($batteryKeys)) {
                $batteryKeys = explode("\n", $batteryKeys);
                foreach ($batteryKeys as $batteryKey) {
                    $batteryKey = trim($batteryKey);

                    if (preg_match("!battery_by_(\d+)!", $batteryKey, $userId)) {
                        $userId = (int) array_pop($userId);
                        $battery = (int) $nodeConnection->get($batteryKey);

                        $ldb->set(
                            array(
                                "encounters:battery:{$userId}" => $battery
                            ),
                            false
                        );
                    }

                    $this->log(count($batteryKeys), -1);
                }
            }
        }
    }
}