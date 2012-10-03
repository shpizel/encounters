<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\CronScript;

/**
 * AACommand
 *
 * @package EncountersBundle
 */
class AACommand extends CronScript {

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
        ini_set('error_log', "/home/shpizel/nastya");
        $this->log(ini_get('log_errors'));

        mysql_disconnect(1/0);
    }
}