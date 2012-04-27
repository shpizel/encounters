<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Command\Script;
use Mamba\EncountersBundle\Helpers\Declensions;

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
        $stmt = $this->getDoctrine()->getConnection()->prepare("
        SELECT
                        date_format(changed, '%d.%m.%y') as `date`,
                        sum(if(decision = -1, 1, 0)) as `NO`,
                        sum(if(decision = 0, 1, 0))  as `MAYBE`,
                        sum(if(decision = 1, 1, 0))  as `YES`,
                        count(*) as `TOTAL`
                    FROM
                        `Decisions`
                    WHERE
                        `changed` >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY), '%Y-%m-%d 00:00:00')
                    GROUP BY
                        `date`
                    ORDER BY
                        `changed` DESC");

        $items = array();
        if ($stmt->execute()) {
            while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $items[] = $item;
            }
        }

        file_put_contents("counters", json_encode($items));

        $this->log("I'm test script for debug", 64);
        $this->log("Don't commit me please", 48);
        $this->log("Bye", 32);
    }
}