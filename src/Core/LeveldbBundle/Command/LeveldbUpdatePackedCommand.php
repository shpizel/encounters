<?php
namespace Core\LeveldbBundle\Command;

use Core\ScriptBundle\Script;


/**
 * LeveldbUpdatePackedCommand
 *
 * @package EncountersBundle
 */
class LeveldbUpdatePackedCommand extends Script {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Leveldb update packed script",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "leveldb:update_packed"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $LevelDb = $this->getLeveldb();

        $Request = $LevelDb->update_packed(
            'struct',
            array('a'=>1),
            array(),
            array('s' => 1)
        );

        $LevelDb->execute();

        $this->log("Result: " . var_export($Request->getResult(), true));

        $Request = $LevelDb->get(array('struct'));
        $LevelDb->execute();

        $this->log("Result: " . var_export($Request->getResult(), true));
    }
}