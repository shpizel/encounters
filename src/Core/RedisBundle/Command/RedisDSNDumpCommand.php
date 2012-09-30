<?php
namespace Core\RedisBundle\Command;

use Core\ScriptBundle\Script;

/**
 * RedisMigrationCommand
 *
 * @package RedisBundle
 */
class RedisMigrationCommand extends Script {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Redis migration tool",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "redis:migrate"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $Redis = $this->getRedis();
        $counters = array(
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0,
            6 => 0,
        );

        foreach ($Redis->getNodes() as $redisNode) {
            foreach (array_merge(range('a', 'z'), range(0, 9)) as $letter) {
                if ($nodeKeys = $Redis->getNodeConnection($redisNode)->keys("{$letter}*")) {


                    //$this->log("Trying to fetch node://{$redisNode['host']}:{$redisNode['port']}/{$redisNode['options']['database']}/{$letter}*", 64);

                    //$this->log(count($nodeKeys) . " key(s) found", 48);
                    foreach ($nodeKeys as $nodeKey) {

                        /** Обрежем если нужно nodeKey */
                        if ($redisNode['options']['prefix'] && strpos($nodeKey, $redisNode['options']['prefix']) === 0) {
                            $nodeKey = substr($nodeKey, strlen($redisNode['options']['prefix']));
                        }

                        if ($redisNode != $Redis->getNodeByKey($nodeKey)) {

                            $nodeKeyType = $Redis->getNodeConnection($redisNode)->type($nodeKey);

                            $srcConnection = $Redis->getNodeConnection($redisNode);
                            $dstConnection = $Redis->getNodeConnectionByKey($nodeKey);
                            $dstNodeInfo   = $Redis->getNodeByKey($nodeKey);

                            if ($nodeKeyType == 1 /** string */) {
                                if ($src = $srcConnection->get($nodeKey)){
                                    file_put_contents("/home/shpizel/rdump/strings.db", base64_encode(json_encode(array($nodeKey=>$src))) . PHP_EOL ,FILE_APPEND);
                                    $counters[1]++;
                                }
                            } elseif ($nodeKeyType == 2 /** set */) {
                                $srcSet = $srcConnection->sMembers($nodeKey);
                                file_put_contents("/home/shpizel/rdump/sets.db", base64_encode(json_encode(array($nodeKey=>$srcSet))) . PHP_EOL ,FILE_APPEND);
//                                foreach ($srcSet as $element) {
//                                    $dstConnection->sAdd($nodeKey, $element);
//                                }
//                                $srcConnection->del($nodeKey);
                                $counters[2]++;
                            } elseif ($nodeKeyType == 3 /** list */) {
                                $srcList = $srcConnection->lGet($nodeKey, 0, -1);
                                file_put_contents("/home/shpizel/rdump/lists.db", base64_encode(json_encode(array($nodeKey=>$srcList))) . PHP_EOL ,FILE_APPEND);
//                                foreach ($srcList as $element) {
//                                    $dstConnection->lPush($nodeKey, $element);
//                                }
//                                $srcConnection->del($nodeKey);
                                $counters[3]++;
                            } elseif ($nodeKeyType == 4 /** zset */) {
                                $srcZSet = $srcConnection->zRange($nodeKey, 0, -1, array('withscores'=>true));
                                file_put_contents("/home/shpizel/rdump/zsets.db", base64_encode(json_encode(array($nodeKey=>$srcZSet))) . PHP_EOL ,FILE_APPEND);
//                                foreach ($srcZSet as $element=>$score){
//                                    $dstConnection->zAdd($nodeKey, $element, $score);
//                                }
//                                $srcConnection->del($nodeKey);
                                $counters[4]++;
                            } elseif ($nodeKeyType == 5 /** hash */) {
                                $srcHash = $srcConnection->hGetAll($nodeKey);
                                file_put_contents("/home/shpizel/rdump/hashes.db", base64_encode(json_encode(array($nodeKey=>$srcHash))) . PHP_EOL ,FILE_APPEND);
//                                foreach ($srcHash as $key=>$val) {
//                                    $dstConnection->hSet($nodeKey, $key, $val);
//                                }
//                                $srcConnection->del($nodeKey);

                                $counters[5]++;

                            } else {
                                $counters[6]++;
                            }

                            $this->log("<info>str</info>: {$counters[1]}, <info>set</info>: {$counters[2]}, <info>list</info>: {$counters[3]}, <info>zset</info>: {$counters[4]}, <info>hash</info>: {$counters[5]}", -1);
                        } else {
                            //$this->log("It has no need to be migrated");
                        }
                    }
                } else {
                    //$this->log("No keys was found", 16);
                }
            }
        }
    }
}