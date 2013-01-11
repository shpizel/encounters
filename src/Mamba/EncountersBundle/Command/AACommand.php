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
//        print_r($this->getMamba()->Anketa()->getInfo('shpizel,jaziqque'));
        print_r($this->getMamba()->Photos()->get(560015854));
    }
}