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
        print_r(\Core\RedisBundle\RedisDSN::getDSNFromString("redis://localhost:6379/3/?timeout=2.5&persistent=true&prefix=node3:"));
    }
}