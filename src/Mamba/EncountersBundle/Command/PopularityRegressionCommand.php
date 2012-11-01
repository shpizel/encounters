<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\Script;

/**
 * PopularityRegressionCommand
 *
 * @package EncountersBundle
 */
class PopularityRegressionCommand extends Script {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Popularity regression",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "popularity:regression"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $Popularity = $this->getPopularityObject();

        $energy = $Popularity->getMaxEnergy();
        $this->log("Starting energy: " . $energy, 64);
        $counter = 0;
        while ($energy > 0) {
            $currentLevel = $Popularity->getLevel($energy);

            $energy -= 100*5*(($currentLevel < 3) ? 3 : $currentLevel);

            $level = $Popularity->getLevel($energy);
            $counter++;

            if ($currentLevel > $level) {
                echo("{$currentLevel}: {$counter}" . PHP_EOL);
                $counter = 0;
            }
        }
    }
}