<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\CronScript;


/**
 * AACommand
 *
 * @package EncountersBundle
 */
class AACommand extends CronScript {

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
        $commands = array(
            "sleep 3 > /dev/null &",
            "sleep 2 > /dev/null &",
            "wait"
        );

        foreach ($commands as $cmd) {
        //    $this->log(system($cmd));
        }

        $this->log((implode(";", $commands)));
        exit();
        system("sleep 3 &", $code);
        $this->log($code);
        system("sleep 3  &", $code);
        $this->log($code);
        system('wait', $code);
        $this->log($code);
        exit();
        $leveldb = $this->getLeveldb();

        $start = microtime(true);
        $c = 0;
        while (true) {
        $leveldb->update_packed(
            array(
                'key' => $key = 'structure' . mt_rand(0, 10000000),
                'inc' => array('k1' => 2, 'k4' => -1),
                'set' => array('k3' => 'Hello world '.time()),
            )
        );

        $s = $leveldb->get(array($key));
        $leveldb->execute();

            //print_r($s->getResult());

            $c++;
            if (microtime(true) - $start >= 5) {
                break;
            }
        }

$this->log($c/5);

    }
}