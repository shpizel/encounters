<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Controller\MessengerController;
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
        echo (new MessengerController())->cleanHTMLMessage('<p fff>111</p><img src="/bundles/encounters/images/pixel.if" class="smile s-23">');

        exit();
        $N = $this->getNo();
        $N->add(560015854, 'test');
        exit();

        /*$data = file("https://dl.dropboxusercontent.com/u/34766282/search.txt");
        $ret = [];
        foreach ($data as $line) {
            $line = trim($line);
            $dataArray = explode("\t", $line);

            print_r($dataArray);
            $date = $dataArray[0];
            $date = explode(".", $date);
            array_shift($date);
            $date = implode(".", $date);

            if (!isset($ret[$date])) {
                $ret[$date] = 0;
            }

            $ret[$date]+= intval(str_replace(",","", $dataArray[1]));
        }
        ksort($ret);
        foreach ($ret as $m=>$v) {
            echo $m . "\t" . $v/28 . "\n";
        }


        exit()
        ;*/for ($i=0;$i<5;$i++) {
            echo ".smile.s-" . ($i+1) . " {" . PHP_EOL;
            $y = $i*16;

            if ($i > 0  ) {
                $y+=$i;
            }

            echo "    background-position: 0 {$y}px;" . PHP_EOL;
            echo "}" . PHP_EOL;
        }
//        var_dump($this->getCountersObject()->incr(560015854, 'mychoice'));
//        $c = 0;
//        $Redis = $this->getRedis();
//        $nodes = $Redis->getNodes();
//
//        foreach ($nodes as $node) {
//            $nodeConnection = $Redis->getNodeConnection($node);
//            $keys = $nodeConnection->keys('battery_by*');
//            $chunks = array_chunk($keys, 10000);
//            //$nodeConnection->multi();
//            foreach ($chunks as $chunk) {
//                $nodeConnection->multi();
//                foreach ($chunk as $key) {
//                    $nodeConnection->del($key);
//                    $c++;
//                    $this->log($c, -1);
//                }
//                $nodeConnection->exec();
//            }
//            //$nodeConnection->exec();
//        }
    }
}