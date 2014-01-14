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
                d1.web_user_id as `user_id`,
                count(*) as `counter`
            FROM
                Encounters.Decisions d1
            INNER JOIN
                Encounters.Decisions d2
            ON
                d1.web_user_id = d2.current_user_id
            WHERE
                d1.current_user_id = d2.web_user_id and
                d1.decision >= 0 and
                d2.decision >= 0
            GROUP BY
                d1.web_user_id",

        /**
         * SQL-запрос для получения данных у кого сколько просмотренных
         *
         * @var str
         */
        SQL_GET_MYCHOICE_COUNT = "
            SELECT
                decisions.web_user_id as `user_id`,
                count(*) as `counter`
            FROM
                Decisions decisions
            WHERE
                decisions.decision >= 0
            GROUP BY
                decisions.web_user_id",

        /**
         * SQL-запрос для получения данных у кого сколько просмотров
         *
         * @var str
         */
        SQL_GET_VISITORS_COUNT = "
            SELECT
                current_user_id as `user_id`,
                count(*) as `counter`
            FROM
                Decisions
            GROUP BY
                current_user_id"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $CountersHelper = $this->getCountersHelper();

        while ($item = $this->getMySQL()->getQuery(self::SQL_GET_MUTUALS_COUNT)->execute()->fetch()) {
            $userId  = (int) $item['user_id'];
            $counter = (int) $item['counter'];

            $CountersHelper->set($userId, 'mutual', $counter);
            if ($CountersHelper->get($userId, 'mutual_unread') > $counter) {
                $CountersHelper->set($userId, 'mutual_unread', $counter);
            }
        }

        while ($item = $this->getMySQL()->getQuery(self::SQL_GET_VISITORS_COUNT)->execute()->fetch()) {
            $userId  = (int) $item['user_id'];
            $counter = (int) $item['counter'];

            $CountersHelper->set($userId, 'visitors', $counter);
            if ($CountersHelper->get($userId, 'visitors_unread') > $counter) {
                $CountersHelper->set($userId, 'visitors_unread', $counter);
            }
        }

        while ($item = $this->getMySQL()->getQuery(self::SQL_GET_MYCHOICE_COUNT)->execute()->fetch()) {
            $userId  = (int) $item['user_id'];
            $counter = (int) $item['counter'];

            $CountersHelper->set($userId, 'mychoice', $counter);
        }
    }
}