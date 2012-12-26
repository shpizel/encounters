<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\Script;

/**
 * AccountCounterCommand
 *
 * @package EncountersBundle
 */
class AccountCounterCommand extends Script {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Account Counter",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "account:counter"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $memory1 = `redis-cli -h memory1 keys 'account*'`;
        $memory2 = `redis-cli -h memory2 keys 'account*'`;

        $memory1 = explode(PHP_EOL, $memory1);
        $memory2 = explode(PHP_EOL, $memory2);

        $counter = 0;
        $Redis = $this->getRedis();

        foreach (array_merge($memory1, $memory2) as $key) {
            if ($key = trim($key)) {
                $value = $Redis->get($key);
                $counter+=$value;

                $this->log($counter, -1);
            }
        }
    }
}