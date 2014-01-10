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
                d.web_user_id as `user_id`, count(*) as `counter`
            FROM
                Encounters.Decisions d INNER JOIN Encounters.Decisions d2 on d.web_user_id = d2.current_user_id
            WHERE
                d.current_user_id = d2.web_user_id and
                d.decision >=0 and
                d2.decision >= 0
            GROUP BY
                d.web_user_id
        ",

        /**
         * SQL-запрос для получения данных у кого сколько просмотренных
         *
         * @var str
         */
        SQL_GET_MYCHOICE_COUNT = "
            SELECT
              d.web_user_id as `user_id`, count(*) as `counter`
            FROM
                Encounters.Decisions d
            WHERE
                d.decision >= 0
            GROUP BY
                d.web_user_id
        ",

        /**
         * SQL-запрос для получения данных у кого сколько просмотров
         *
         * @var str
         */
        SQL_GET_VISITORS_COUNT = "
            SELECT DISTINCT
                current_user_id as `user_id`,
                count(*) as `counter`
            FROM
                Encounters.Decisions
            GROUP BY
                current_user_id
        "
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        while ($item = $this->getMySQL()->getQuery(self::SQL_GET_MUTUALS_COUNT)->execute()->fetch()) {
            $userId  = (int) $item['user_id'];
            $counter = (int) $item['counter'];

            $this->getCountersHelper()->set($userId, 'mutual', $counter);
        }

        while ($item = $this->getMySQL()->getQuery(self::SQL_GET_VISITORS_COUNT)->execute()->fetch(PDO::FETCH_ASSOC)) {
            $userId  = (int) $item['user_id'];
            $counter = (int) $item['counter'];

            $this->getCountersHelper()->set($userId, 'visitors', $counter);
        }

        /*while ($item = $this->getMySQL->getQuery(self::SQL_GET_MYCHOICE_COUNT)->execute()->fetch(PDO::FETCH_ASSOC)) {
            $userId  = (int) $item['user_id'];
            $counter = (int) $item['counter'];

            $this->getCountersHelper()->set($userId, 'mychoice', $counter);
        }*/
    }
}