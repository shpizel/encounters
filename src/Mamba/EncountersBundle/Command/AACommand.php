<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Command\Script;
use Mamba\EncountersBundle\Helpers\SearchPreferences;
use PDO;

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
        echo $this->getPopularityObject()->getLevel(4555) . "\n";
        //print_r($this->getMamba()->nocache()->Anketa()->getInfo(363561329));


//        $stmt = $this->getEntityManager()->getConnection()->prepare("SELECT * FROM Decisions limit 100000");
//        if ($stmt->execute()) {
//            $data = array();
//            while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
//                $data[] = (int)$item['web_user_id'];
//            }
//
//            $data[] = 1367088859234;
//
//            $currentQueue = array_reverse($data);
//            $currentQueue = array_chunk($currentQueue, 100);
//            foreach ($currentQueue as $key=>$subQueue) {
//                $currentQueue[$key] = array_reverse($subQueue);
//            }
//            $currentQueue = array_reverse($currentQueue);
//
//            $Mamba->multi();
//            foreach ($currentQueue as $subQueue) {
//                $Mamba->Anketa()->getInfo($subQueue);
//            }
//            $anketaInfoArray = $Mamba->exec();
//
//            echo "parsed\n";
//            foreach ($anketaInfoArray as $chunk) {
//                foreach ($chunk as $dataArray) {
//                    if (isset($dataArray['location']) &&  isset($dataArray['flags']) && isset($dataArray['familiarity']) && isset($dataArray['other'])) {
////                        echo "ok\n";
//                    } else {
//                        echo "pizda";
//                    }
//                }
//            }
//        }

//        echo count($data) . "\n";
//        $start = time();
//
//        foreach ($data as $key => $item) {
//            var_dump($this->getViewedQueueObject()->get($item[0], $item[1]));
//
//            if ($key % 1000 == 0) {
//                echo $key . "\t" . (time() - $start) . "\n";
//                exit();
//            }
//        }
//
//        echo time() - $start;
//        echo "\n";
//
//        $this->log("I'm test script for debug", 64);
//        $this->log("Don't commit me please", 48);
//        $this->log("Bye", 32);
    }
}