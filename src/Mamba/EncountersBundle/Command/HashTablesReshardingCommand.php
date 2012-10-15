<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\Script;

/**
 * HashTablesReshardingCommand
 *
 * @package EncountersBundle
 */
class HashTablesReshardingCommand extends Script {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Hash tables resharding script",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "redis:hash:resharding"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $Redis = $this->getRedis();

        /**
         * 1. batteries
         */
        $this->log("Fetching batteries..");
        $items = $Redis->hGetAll("batteries");
        $this->log("SUCCESS", 64);

        $counter = 0;
        foreach ($items as $userId => $value) {
            $Redis->set("battery_by_" . $userId, $value);
            $counter++;

            $this->log(number_format($counter) . "/" . number_format(count($items)) , -1);
        }

        /**
         * 2. energies
         */
        $this->log("Fetching energies..");
        $items = $Redis->hGetAll("energies");
        $this->log("SUCCESS", 64);

        $counter = 0;
        foreach ($items as $userId => $value) {
            $Redis->set("energy_by_" . $userId, $value);
            $counter++;

            $this->log(number_format($counter) . "/" . number_format(count($items)) , -1);
        }

        /**
         * 3. users_search_preferences
         */
        $this->log("Fetching users search preferences..");
        $items = $Redis->hGetAll("users_search_preferences");
        $this->log("SUCCESS", 64);

        $counter = 0;
        foreach ($items as $userId => $value) {
            $Redis->set("search_preferences_by_" . $userId, $value);
            $counter++;

            $this->log(number_format($counter) . "/" . number_format(count($items)) , -1);
        }
    }
}