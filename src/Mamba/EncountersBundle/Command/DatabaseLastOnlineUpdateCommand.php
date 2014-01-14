<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\CronScript;
use PDO;

use Mamba\EncountersBundle\EncountersBundle;

/**
 * DatabaseLastOnlineUpdateCommand
 *
 * @package EncountersBundle
 */
class DatabaseLastOnlineUpdateCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Update users last online",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:database:user:last:online:update"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $iterations = $this->iterations;
        while
        (
            (!$this->getMemcache()->get("cron:stop") || (($stopCommandTimeStamp = (int) $this->getMemcache()->get("cron:stop")) && ($stopCommandTimeStamp < $this->started))) &&
            ((time() - $this->started < $this->lifetime) || !$this->lifetime) &&
            filemtime(__FILE__) < $this->started &&
            ((memory_get_usage() < $this->memory) || !$this->memory) &&
            $this->iterations-- &&
            $this->log(($iterations - $this->iterations) . " iteration:", 48)
        ) {
            if (!($result = $this->updateUsersLastOnline())) {
                $this->log("No results");
                sleep(60);
            } else {
                $this->log("Updated " . $result . " users");
            }
        }

        $this->log("Bye", 48);
    }

    /**
     * Обновление таблицы UsersLastOnline
     *
     * @param $job
     */
    public function updateUsersLastOnline() {
        $count = 0;

        $selectQuery = $this->getMySQL()->getQuery("
            SELECT
                online.user_id
            FROM
                Encounters.UserLastOnline online
            LEFT JOIN
                Encounters.UserInfo info
            ON
                info.user_id = online.user_id
            WHERE
                info.is_app_user = 1 AND
                online.changed < DATE_SUB(NOW(), INTERVAL 6 HOUR)
            LIMIT
                30
            FOR UPDATE
        ");

        $updateQuery = $this->getMySQL()->getQuery("
            UPDATE
                `Encounters`.`UserLastOnline`
            SET
                `last_online` = :last_online
            WHERE
                `user_id` = :user_id
        ");

        $users = [];
        $this->log("Fetching 30 users ids for update..");
        if ($selectQuery->execute()->getResult()) {
            $this->log("SQL-query completed");
            while ($row = $selectQuery->fetch()) {
                $users[] = (int) $row['user_id'];
            }

            $this->log("Fetched " . count($users) . " users");
            $this->log(implode(", ", $users));

            if (count($users)) {
                $this->log("Fetching API result for last online");
                if ($apiResult = $this->getMamba()->nocache()->Anketa()->isOnline($users)) {
                    $this->log("API query completed");
                    $this->log(var_export($apiResult, true));

                    foreach ($apiResult as $dataArray) {
                        $userId = (int) $dataArray['anketa_id'];
                        $lastOnline = (int) $dataArray['is_online'];
                        if ($lastOnline) {
                            if ($lastOnline == 1) {
                                $lastOnline = time();
                            }

                            $updateQuery->bindArray([
                                ['user_id', $userId],
                                ['last_online', $lastOnline],
                            ]);

                            if ($updateQuery->execute()->getResult()) {
                                $count++;
                            }
                        }
                    }
                }
            }
        }

        return $count;
    }
}