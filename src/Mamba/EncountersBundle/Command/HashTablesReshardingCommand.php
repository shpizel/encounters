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

        // users_search_preferences
        $this->log("Fetching users_search_preferences..");
        $items = $Redis->hGetAll("users_search_preferences");
        $this->log("SUCCESS", 64);

        $counter = 0;
        foreach ($items as $userId => $value) {
            $Redis->set("search_preferences_by_" . $userId, $value);
            $counter++;

            $this->log(number_format($counter) . "/" . number_format(count($items)) , -1);
        }

        //users_platform_last_query
        $this->log("Fetching users_platform_last_query..");
        $items = $Redis->hGetAll("users_platform_last_query");
        $this->log("SUCCESS", 64);

        $counter = 0;
        foreach ($items as $userId => $value) {
            $Redis->set("platform_last_query_by_" . $userId, $value);
            $counter++;

            $this->log(number_format($counter) . "/" . number_format(count($items)) , -1);
        }

        //users_platform_settings
        $this->log("Fetching users_platform_settings..");
        $items = $Redis->hGetAll("users_platform_settings");
        $this->log("SUCCESS", 64);

        $counter = 0;
        foreach ($items as $userId => $value) {
            $Redis->set("platform_settings_by_" . $userId, $value);
            $counter++;

            $this->log(number_format($counter) . "/" . number_format(count($items)) , -1);
        }
    }
}