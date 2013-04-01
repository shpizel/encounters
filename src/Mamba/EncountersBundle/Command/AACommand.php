<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\Script;
use Mamba\EncountersBundle\Tools\Gifts\Gifts;

/**
 * AACommand
 *
 * @package EncountersBundle
 */
class AACommand extends Script {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "AA script",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "AA"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        var_dump($this->getCountersObject()->incr(560015854, 'mychoice'));
//        $c = 0;
//        $Redis = $this->getRedis();
//        $nodes = $Redis->getNodes();
//
//        foreach ($nodes as $node) {
//            $nodeConnection = $Redis->getNodeConnection($node);
//            $keys = $nodeConnection->keys('battery_by*');
//            $chunks = array_chunk($keys, 10000);
//            //$nodeConnection->multi();
//            foreach ($chunks as $chunk) {
//                $nodeConnection->multi();
//                foreach ($chunk as $key) {
//                    $nodeConnection->del($key);
//                    $c++;
//                    $this->log($c, -1);
//                }
//                $nodeConnection->exec();
//            }
//            //$nodeConnection->exec();
//        }
    }
}