<?php
namespace Mamba\EncountersBundle\Command;

use Core\ScriptBundle\Script;

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
        $keys = array_map(function($line) {
            return trim($line);
        }, file("/home/shpizel/rdb/0-1-5-3.keys"));

        $source = $this->getRedis()->getNodeConnectionByNodeNumber(0);

        $source->multi();
        foreach ($keys as $key) {
            $source->hGetAll($key);
        }
        $ret = $source->exec();

        print_r($ret[730]);
    }
}