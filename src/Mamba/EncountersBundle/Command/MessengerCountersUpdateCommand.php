<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\CronScript;
use PDO;

use Mamba\EncountersBundle\EncountersBundle;

/**
 * MessengerCountersUpdateCommand
 *
 * @package EncountersBundle
 */
class MessengerCountersUpdateCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Messenger counters updater",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:messenger:counters:update",

        /**
         * SQL-запрос обновления таблицы юзеров
         *
         * @var str
         */
        SQL_GET_USER_UNREAD_MESSAGES = "
            SELECT
                SUM(`unread_count`) as `messages_unread`
            FROM
                `Messenger`.`Contacts`
            WHERE
                `sender_id` = :user_id
        "
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $worker = $this->getGearmanWorker();

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_MESSENGER_UPDATE_COUNTERS_FUNCTION_NAME, function($job) use($class) {
            return $class->updateUserUnreadMessagesCounter($job);
        });

        $iterations = $this->iterations;
        while
        (
            (!$this->getMemcache()->get("cron:stop") || (($stopCommandTimeStamp = (int) $this->getMemcache()->get("cron:stop")) && ($stopCommandTimeStamp < $this->started))) &&
            ((time() - $this->started < $this->lifetime) || !$this->lifetime) &&
            filemtime(__FILE__) < $this->started &&
            ((memory_get_usage() < $this->memory) || !$this->memory) &&
            $this->iterations-- &&
            $this->log(($iterations - $this->iterations) . " iteration:", 48) &&
            (@$worker->work() || $worker->returnCode() == GEARMAN_TIMEOUT)
        ) {
            if ($worker->returnCode() == GEARMAN_TIMEOUT) {
                $this->log("Timed out", 48);
                continue;
            } elseif ($worker->returnCode() != GEARMAN_SUCCESS) {
                $this->log("Failed", 16);
                break;
            } elseif ($worker->returnCode() == GEARMAN_SUCCESS) {
                $this->log("Completed", 64);
            }
        }

        $this->log("Bye", 48);
    }

    /**
     * Обновление таблицы Lastaccess
     *
     * @param $job
     */
    public function updateUserUnreadMessagesCounter($job) {
        list($userId) = array_values(unserialize($job->workload()));

        $this->log("Got task for <info>user_id</info> = {$userId}");

        $stmt = $this->getEntityManager()->getConnection()->prepare(self::SQL_GET_USER_UNREAD_MESSAGES);
        $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);

        $result = $stmt->execute();
        if (!$result) {
            throw new \Core\ScriptBundle\CronScriptException('Could not execute query');
        } else {
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($this->getCountersHelper()->set($userId, 'messages_unread', $row['messages_unread'])) {
                    $this->log('SUCCESS', 64);
                } else {
                    $this->log('FAILED', 16);
                }
            }
        }
    }
}