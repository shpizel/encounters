<?php
namespace Core\LeveldbBundle\Command;

use Core\ScriptBundle\Script;


/**
 * LeveldbClearCommand
 *
 * @package EncountersBundle
 */
class LeveldbClearCommand extends Script {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Leveldb clear script",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "leveldb:clear"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $LevelDb = $this->getLeveldb();

        $counter = 0;

        while (true) {
            $Request = $LevelDb->get_range(null, null, 5000);
            $LevelDb->execute();

            if ($result = $Request->getResult()) {
                $keys = array_keys($result);

                $Request = $LevelDb->del($keys);
                $LevelDb->execute();

                $this->log($counter+=count($Request->getResult()) . " keys removed");
            } else {
                break;
            }
        }

        $this->log("OK", 64);
    }
}