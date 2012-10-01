<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\CronScript;
use PDO;

/**
 * DatabaseDecisionsCleanCommand
 *
 * @package EncountersBundle
 */
class DatabaseDecisionsCleanCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Database decisions cleaner",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:database:decisions:clean",

        /**
         * Decisions clean sql
         *
         * @var str
         */
        DECISIONS_CLEAN_SQL = "
            DELETE FROM
                `Decisions`
            WHERE
                `changed` <= date_format(DATE_SUB(NOW(), INTERVAL 30 DAY), '%Y-%m-%d 00:00:00')
        "
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $this->getEntityManager()->getConnection()->prepare(self::DECISIONS_CLEAN_SQL)->execute();
    }
}