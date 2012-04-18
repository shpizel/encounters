<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Command\Script;
use Mamba\EncountersBundle\Helpers\SearchPreferences;
use Mamba\EncountersBundle\Helpers\Declensions;
use PDO;

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
        $this->getNotificationsObject()->add(560015854, "suka");
        $this->getNotificationsObject()->add(560015854, "bla");
    }
}