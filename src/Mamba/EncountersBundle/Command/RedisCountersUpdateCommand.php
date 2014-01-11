<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\CronScript;
use PDO;

/**
 * RedisCountersUpdateCommand
 *
 * @package EncountersBundle
 */
class RedisCountersUpdateCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Redis counters update",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:redis:counters:update",


        /**
         * SQL-запрос для получения данных у кого сколько совпадений
         *
         * @var str
         */
        SQL_GET_MUTUALS_COUNT = "
            SELECT
                d1.web_user_id as user_id,
                count(*) as new_mutual,
                counters.mutual as old_mutual
            FROM
                Encounters.Decisions d1
            INNER JOIN
                Encounters.Decisions d2
            ON
                d1.web_user_id = d2.current_user_id
            LEFT JOIN
                UserCounters counters
            ON
                counters.user_id = d1.web_user_id
            WHERE
                d1.current_user_id = d2.web_user_id and
                d1.decision >= 0 and
                d2.decision >= 0
            GROUP BY
                d1.web_user_id
            HAVING
                new_mutual <> old_mutual",

        /**
         * SQL-запрос для получения данных у кого сколько просмотров
         *
         * @var str
         */
        SQL_GET_VISITORS_COUNT = "
            SELECT
                decisions.current_user_id as user_id,
                count(*) as new_visitors,
                counters.visitors as old_visitors
            FROM
                Decisions decisions
            LEFT JOIN
                UserCounters counters
            ON
                counters.user_id = decisions.current_user_id
            GROUP BY
                decisions.current_user_id
            HAVING
                new_visitors <> old_visitors",

        /**
         * SQL-запрос для получения данных у кого сколько просмотренных
         *
         * @var str
         */
        SQL_GET_MYCHOICE_COUNT = "
            SELECT
                decisions.web_user_id as user_id,
                count(*) as new_mychoice,
                counters.mychoice as old_mychoice
            FROM
                Decisions decisions
            LEFT JOIN
                UserCounters counters
            ON
                counters.user_id = decisions.web_user_id
            WHERE
                decisions.decision >= 0
            GROUP BY
                decisions.web_user_id
            HAVING
                new_mychoice <> old_mychoice"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $CountersHelper = $this->getCountersHelper();

        $this->log("Querying mutuals counts..", 64);
        $Query = $this->getMySQL()->getQuery(self::SQL_GET_MUTUALS_COUNT);
        if ($Query->execute()->getResult()) {
            $this->log("SQL query completed with " . ($rowCount = $Query->getStatement()->rowCount()) . " results", 64);

            $processedCount = $progress = $prevProgress = 0;
            while ($row = $Query->fetch()) {
                $userId  = (int) $row['user_id'];
                $counter = (int) $row['new_mutual'];

                $CountersHelper->set($userId, 'mutual', $counter);
                if ($CountersHelper->get($userId, 'mutual_unread') > $counter) {
                    $CountersHelper->set($userId, 'mutual_unread', $counter);
                }

                $processedCount++;
                $progress = round($processedCount*100/$rowCount, 0);
                if ($progress > $prevProgress) {
                    $prevProgress = $progress;
                    $this->log("{$progress} %");
                }
            }
        } else {
            $this->log("SQL query not returned results", 16);
        }

        $this->log("Querying visitors counts..", 64);
        $Query = $this->getMySQL()->getQuery(self::SQL_GET_VISITORS_COUNT);
        if ($Query->execute()->getResult()) {
            $this->log("SQL query completed with " . ($rowCount = $Query->getStatement()->rowCount()) . " results", 64);

            $processedCount = $progress = $prevProgress = 0;
            while ($row = $Query->fetch()) {
                $userId  = (int) $row['user_id'];
                $counter = (int) $row['new_visitors'];

                $CountersHelper->set($userId, 'visitors', $counter);
                if ($CountersHelper->get($userId, 'visitors_unread') > $counter) {
                    $CountersHelper->set($userId, 'visitors_unread', $counter);
                }

                $processedCount++;
                $progress = round($processedCount*100/$rowCount, 0);
                if ($progress > $prevProgress) {
                    $prevProgress = $progress;
                    $this->log("{$progress} %");
                }
            }
        } else {
            $this->log("SQL query not returned results", 16);
        }

        $this->log("Querying my choice counts..", 64);
        $Query = $this->getMySQL()->getQuery(self::SQL_GET_MYCHOICE_COUNT);
        if ($Query->execute()->getResult()) {
            $this->log("SQL query completed with " . ($rowCount = $Query->getStatement()->rowCount()) . " results", 64);

            $processedCount = $progress = $prevProgress = 0;
            while ($row = $Query->fetch()) {
                $userId  = (int) $row['user_id'];
                $counter = (int) $row['new_mychoice'];

                $CountersHelper->set($userId, 'mychoice', $counter);

                $processedCount++;
                $progress = round($processedCount*100/$rowCount, 0);
                if ($progress > $prevProgress) {
                    $prevProgress = $progress;
                    $this->log("{$progress} %");
                }
            }
        } else {
            $this->log("SQL query not returned results", 16);
        }
    }
}