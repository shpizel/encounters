<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Command\Script;

/**
 * CronScriptStopCommand
 *
 * @package EncountersBundle
 */
class CronScriptStopCommand extends Script {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Stops cron scripts",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:stop"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $Memcache = $this->getContainer()->get('memcache');
        if ($crons = $this->getCronScriptsList()) {
            $Memcache->add("cron:stop", time(), 3600);

            $timeout = 0;
            while ($crons = $this->getCronScriptsList()) {
                $this->log("Waiting for " . implode(", ", $crons));
                sleep(1);
                $timeout++;
            }

            $Memcache->delete("cron:stop");
            $this->log("OK", 64);
        } else {
            $this->log("No cron scripts were found!", 16);
        }
    }

    /**
     * Возвращает список уже запущенных кронов
     */
    private function getCronScriptsList() {
        $result = array();
        exec('ps ax | grep php | grep "cron:" | grep -v "cron:stop"', $result);
        array_filter($result, function($item) {
            return (bool) preg_match("!console cron:\w+!i", $item);
        });

        return
            array_filter(
                array_map(function($item) {
                    $item = explode(" ", $item);
                    return (int) array_shift($item);
                }, $result),
                function($item) {
                    return (bool) $item;
                }
            )
        ;
    }
}
