<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\Script;
use Mamba\EncountersBundle\Tools\Gifts\Gifts;

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
        $Energy = $this->getEnergyObject();
        var_dump($Energy->get(1));
        var_dump($Energy->set(1, 2));
        var_dump($Energy->incr(1));
        var_dump($Energy->decr(1));



    }
}