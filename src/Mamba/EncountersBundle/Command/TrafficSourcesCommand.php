<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\Script;

/**
 * TrafficSourcesCommand
 *
 * @package EncountersBundle
 */
class TrafficSourcesCommand extends Script {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Traffic sources script",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "traffic:sources"
    ;

    private $result = [];

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $limit = 1000000;
        $counter = 0;
        while (FALSE !== ($line = fgets(STDIN))) {
            $counter++;
            $this->processLine($line);
            $this->log($counter, -1);

            if ($counter > $limit) break;
        }

        print_r($this->result);
    }

    private function processLine($line) {
        $line = trim($line);

        list($date, $url) = explode(" ", $line);
        $date = explode(":", $date);
        $date = array_shift($date);
        $date = str_replace([']', '['], '', $date);
        $date = str_replace(['/'], ' ', $date);
        $date = date("Y-m-d", strtotime($date));

        if (preg_match("!extra=([^&=]+)!", $url, $result)) {
            if (($extra = array_pop($result)) && !is_numeric($extra)) {


                if (!isset($this->result[$date])) {
                    $this->result[$date] = [];
                }

                if (!isset($this->result[$date][$extra])) {
                    $this->result[$date][$extra] = 0;
                }

                $this->result[$date][$extra]++;
            }
        }
    }
}