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
                $this->log($result);
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
                `user_id`
            FROM
                `Encounters`.`UserLastOnline`
            WHERE
                `changed` < DATE_SUB(NOW(), INTERVAL 6 HOUR)
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
        if ($result = $selectQuery->execute()->getResult()) {
            while ($row = $selectQuery->fetch()) {
                $users[] = (int) $row['user_id'];
            }

            if ($users) {
                if ($apiResult = $this->getMamba()->nocache()->Anketa()->isOnline($users)) {
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