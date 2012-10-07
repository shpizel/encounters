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

            $prevLevel = $currentLevel - 1;
            $points = ($Popularity->getLevels()[$currentLevel] - (($prevLevel < $currentLevel) ? $Popularity->getLevels()[$prevLevel] : 0)) / $currentLevel;

            $this->log($currentLevel . "\t" . $prevLevel . "\t" . $points, 16);

            $energy-= $points;

            $level = $Popularity->getLevel($energy);
            $counter++;

            if ($currentLevel > $level) {
                $this->log("{$level} : {$counter}");
                $counter = 0;
            }
        }
    }
}