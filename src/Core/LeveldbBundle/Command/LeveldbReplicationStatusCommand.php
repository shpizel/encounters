<?php
namespace Core\LeveldbBundle\Command;

use Core\ScriptBundle\Script;


/**
 * LeveldbReplicationStatusCommand
 *
 * @package EncountersBundle
 */
class LeveldbReplicationStatusCommand extends Script {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Leveldb replication status script",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "leveldb:replication:status"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $LevelDb = $this->getLeveldb();

        $Request = $LevelDb->rep_status();
        $LevelDb->execute();

        $this->log("Result: " . var_export($Request->getResult(), true));
    }
}