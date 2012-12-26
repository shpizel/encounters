<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\CronScript;

/**
 * PhotolineCleanerCommand
 *
 * @package EncountersBundle
 */
class PhotolineCleanerCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Photoline cleaner",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:photoline:cleaner"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {

    }
}