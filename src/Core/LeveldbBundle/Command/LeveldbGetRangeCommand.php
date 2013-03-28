<?php
namespace Core\LeveldbBundle\Command;

use Core\ScriptBundle\Script;


/**
 * LeveldbGetRangeCommand
 *
 * @package EncountersBundle
 */
class LeveldbGetRangeCommand extends Script {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Leveldb get range script",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "leveldb:get:range"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $LevelDb = $this->getLeveldb();

        /**
         * Multi set
         *
         * @author shpizel
         */
        $Request = $LevelDb->set($data = array(
            "set:example:1" => time(),
            "set:example:2" => time() + mt_rand(1000, 1000000),
            "set:example:3" => time() + mt_rand(1000, 1000000),
        ));

        $this->log("Setting " . var_export($data, true));

        $LevelDb->execute();

        $this->log("Result: " . var_export($Request->getResult(), true));

        $this->log("Getting keys: " . var_export($keys = array_keys($data), true));
        $Request = $LevelDb->get($keys);
        $LevelDb->execute();

        $this->log("Result: " . var_export($Request->getResult(), true));

        /**
         * Getting range
         *
         * @author shpizel
         */
        $Request = $LevelDb->get_range('example', null, 10);

        $this->log("Getting range example*");

        $LevelDb->execute();

        $this->log("Result: " . var_export($Request->getResult(), true));

        /**
         * Removing keys
         *
         * @author shpizel
         */
        $Request = $LevelDb->del($keys);
        $LevelDb->execute();

        $this->log("Result: " . var_export($Request->getResult(), true));
    }
}