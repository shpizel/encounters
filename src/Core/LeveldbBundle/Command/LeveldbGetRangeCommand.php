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
        SCRIPT_NAME = "leveldb:get_range"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $LevelDb = $this->getLeveldb();

        /**
         * Getting range
         *
         * @author shpizel
         */
        $Request = $LevelDb->get_range(null, null, 100);

        $LevelDb->execute();

        $this->log("Result: " . var_export($Request->getResult(), true));
    }
}