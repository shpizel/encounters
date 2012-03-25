<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\CronScript;
use Doctrine\ORM\Query\ResultSetMapping;
use PDO;

/**
 * DatabaseCleanCommand
 *
 * @package EncountersBundle
 */
class DatabaseCleanCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Database cleaner",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:database:clean",

        /**
         * SQL-запрос для получения уникальных айдишников пользователей из базы
         *
         * @var str
         */
        SQL_GET_UNIQUE_USERS_IDS = "
            SELECT DISTINCT
                id, web_user_id as `user_id`
            FROM
                Encounters.Decisions
            UNION SELECT DISTINCT
                id, current_user_id as `user_id`
            FROM
                Encounters.Decisions
            ORDER BY
                id DESC
        ",

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
            ORDER BY
                `counter`
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
            ORDER BY
                `counter`
        ",

        /**
         * SQL-запрос для получения данных у кого сколько просмотров
         *
         * @var str
         */
        SQL_GET_VISITORS_COUNT = "select distinct current_user_id as `user_id`, count(*) as `counter` from Encounters.Decisions where current_user_id in (select distinct web_user_id from Encounters.Decisions) group by current_user_id order by `counter`;"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $Mamba = $this->getMamba();

//        $stmt = $this->getContainer()->get('doctrine')->getEntityManager()->getConnection()->prepare(self::SQL_GET_UNIQUE_USERS_IDS);
//        $stmt->execute();
//
//        $chunkId = 0;
//        $chunk = array();
//        while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
//            $chunk[] = $userId = (int) $item['user_id'];
//
//            /** Очищаем счетчик mutual */
//            $this->getCountersObject()->set($userId, 'mutual', 0);
//
//            if (count($chunk) == 100) {
//                $chunkId++;
//                $result = $Mamba->Anketa()->getInfo($chunk, array());
//                $existingIds = array();
//                foreach ($result as $item) {
//                    $existingIds[] = $item['info']['oid'];
//                }
//
//                if ($notExists = array_values(array_diff($chunk, $existingIds))) {
//                    $this->log($chunkId . ":<error>" . count($notExists) . "</error>:<comment>" . round(memory_get_usage()/1024/1024, 0) . "</comment>");
//
//                    $sql = "DELETE FROM Encounters.Decisions WHERE web_user_id IN (" . implode(", ", $notExists) . ") OR current_user_id IN (" . implode(", ", $notExists) . ")";
//                    $this->log($sql, 32);
//                    $this->getContainer()->get('doctrine')->getConnection()->prepare($sql)->execute();
//
//                } else {
//                    $this->log($chunkId . ":<info>0</info>:<comment>" . round(memory_get_usage()/1024/1024, 0) . "</comment>");
//                }
//
//                $chunk = array();
//            }
//        }
//
//        if ($chunk) {
//            $chunkId++;
//            $result = $Mamba->Anketa()->getInfo($chunk, array());
//            $existingIds = array();
//            foreach ($result as $item) {
//                $existingIds[] = $item['info']['oid'];
//            }
//
//            if ($notExists = array_values(array_diff($chunk, $existingIds))) {
//                $this->log($chunkId . ":<error>" . count($notExists) . "</error>:<comment>" . round(memory_get_usage()/1024/1024, 0) . "</comment>");
//
//                $sql = "DELETE FROM Encounters.Decisions WHERE web_user_id IN (" . implode(", ", $notExists) . ") OR current_user_id IN (" . implode(", ", $notExists) . ")";
//                $this->log($sql, 32);
//                $this->getContainer()->get('doctrine')->getConnection()->prepare($sql)->execute();
//
//            } else {
//                $this->log($chunkId . ":<info>0</info>:<comment>" . round(memory_get_usage()/1024/1024, 0) . "</comment>");
//            }
//
//            $chunk = array();
//        }

    }
}