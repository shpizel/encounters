<?php
namespace Core\LeveldbBundle\Command;

use Core\ScriptBundle\Script;


/**
 * LeveldbSimpleExampleCommand
 *
 * @package EncountersBundle
 */
class LeveldbSimpleExampleCommand extends Script {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Leveldb simple example script",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "leveldb:simple:example"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $LevelDb = $this->getLeveldb();

        /**
         * Single set
         *
         * @author shpizel
         */
        $Request = $LevelDb->set(array(
            $key = "set:example:1" => $value = time(),
        ));

        $this->log("Setting {$key}={$value}");

        $LevelDb->execute();

        $this->log("Result: " . var_export($Request->getResult(), true));

        $this->log("Getting key={$key}");
        $Request = $LevelDb->get(array($key));
        $LevelDb->execute();

        $this->log("Result: " . var_export($Request->getResult(), true));

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
         * Removing keys
         *
         * @author shpizel
         */
        $Request = $LevelDb->del($keys);
        $LevelDb->execute();

        $this->log("Result: " . var_export($Request->getResult(), true));
    }
}