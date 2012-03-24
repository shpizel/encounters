<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script;

/**
 * RedisBatteriesSumCommand
 *
 * @package EncountersBundle
 */
class RedisBatteriesSumCommand extends Script {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Redis batteries sum",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "redis:batteries:sum"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $sum = 0;
        foreach ($this->getRedis()->hGetAll("batteries") as $charge) {
            $sum+= (int) $charge;
        }

        $this->log($sum, 64);
    }
}