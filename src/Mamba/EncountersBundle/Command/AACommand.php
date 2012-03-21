<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script;
use AppKernel;

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
        $this->log("I'm test script for debug", 64);
        $this->log("Don't commit me please", 48);
        $this->log("Bye", 32);
    }
}