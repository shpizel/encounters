<?php
namespace Mamba\EncountersBundle\Command;

use Core\ScriptBundle\CronScript;

/**
 * CronScriptRestartCommand
 *
 * @package EncountersBundle
 */
class CronScriptRestartCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Restart all cron scripts",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:restart"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $this->getMemcache()->set('cron:stop', time());
    }
}