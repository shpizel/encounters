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
        $Connection = $this->getEntityManager()->getConnection();

        $selectStatement = $Connection
            ->prepare(
                "
                    SELECT
                        `user_id`
                    FROM
                        `Encounters`.`UserLastOnline`
                    WHERE
                        `updated` < DATE_SUB(NOW(), INTERVAL 6 HOUR)
                    LIMIT
                        30
                    FOR UPDATE
                "
            )
        ;

        $updateStatement = $Connection
            ->prepare(
                "
                    UPDATE
                        `Encounters`.`UserLastOnline`
                    SET
                        `last_online` = :last_online
                    WHERE
                        `user_id` = :user_id
                "
            )
        ;

        $users = [];
        if ($result = $selectStatement->execute()) {
            while ($row = $selectStatement->fetch(PDO::FETCH_ASSOC)) {
                $users[] = (int) $row['user_id'];
            }

            if ($users) {
                if ($apiResult = $this->getMamba()->Anketa()->isOnline($users)) {
                    foreach ($apiResult as $dataArray) {
                        $userId = (int) $dataArray['anketa_id'];
                        $lastOnline = (int) $dataArray['is_online'];
                        if ($lastOnline) {
                            if ($lastOnline == 1) {
                                $lastOnline = time();
                            }

                            $updateStatement->bindParam('user_id', $userId, PDO::PARAM_INT);
                            $updateStatement->bindParam('last_online', $lastOnline, PDO::PARAM_INT);

                            if ($updateStatement->execute()) {
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