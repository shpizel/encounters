<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\CronScript;
use PDO;

use Mamba\EncountersBundle\EncountersBundle;

/**
 * DatabaseUserCountersUpdateCommand
 *
 * @package EncountersBundle
 */
class DatabaseUserCountersUpdateCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Update users counters",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:database:user:counters:update",

        /**
         * SQL-запрос
         *
         * @var str
         */
        SQL_USER_COUNTERS_UPDATE = "
            INSERT INTO
                Encounters.UserCounters
            SET
                `user_id`         = :user_id,
                `mychoice`        = :mychoice,
                `visitors`        = :visitors,
                `visitors_unread` = :visitors_unread,
                `mutual`          = :mutual,
                `mutual_unread`   = :mutual_unread,
                `messages_unread` = :messages_unread,
                `events_unread`   = :events_unread
            ON DUPLICATE KEY UPDATE
                `mychoice`        = :mychoice,
                `visitors`        = :visitors,
                `visitors_unread` = :visitors_unread,
                `mutual`          = :mutual,
                `mutual_unread`   = :mutual_unread,
                `messages_unread` = :messages_unread,
                `events_unread`   = :events_unread
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
        $worker->addFunction(EncountersBundle::GEARMAN_DATABASE_USER_COUNTERS_UPDATE_FUNCTION_NAME, function($job) use($class) {
            return $class->updateUserCounters($job);
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
    public function updateUserCounters($job) {
        list($userId,) = array_values(unserialize($job->workload()));

        $this->log("Got task for <info>user_id</info> = {$userId}");

        $counters = $this->getCountersHelper()->getMulti(
            [(int) $userId],
            ['mychoice', 'visitors', 'visitors_unread', 'mutual', 'mutual_unread', 'messages_unread', 'events_unread']
        );

        $stmt = $this->getEntityManager()->getConnection()->prepare(self::SQL_USER_COUNTERS_UPDATE);
        $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue($key = 'mychoice', $_mychoice = $counters[(int) $userId][$key], PDO::PARAM_INT);
        $stmt->bindValue($key = 'visitors', $_visitors = $counters[(int) $userId][$key], PDO::PARAM_INT);
        $stmt->bindValue($key = 'visitors_unread', $_visitorsUnread = $counters[(int) $userId][$key], PDO::PARAM_INT);
        $stmt->bindValue($key = 'mutual', $_mutual = $counters[(int) $userId][$key], PDO::PARAM_INT);
        $stmt->bindValue($key = 'mutual_unread', $_mutualUnread = $counters[(int) $userId][$key], PDO::PARAM_INT);
        $stmt->bindValue($key = 'messages_unread', $_messagesUnread = $counters[(int) $userId][$key], PDO::PARAM_INT);
        $stmt->bindValue($key = 'events_unread', $_eventsUnread = $counters[(int) $userId][$key], PDO::PARAM_INT);

        if (!($result = $stmt->execute())) {
            throw new \Core\ScriptBundle\CronScriptException('Unable to store data to UserCounters');
        } else {
            $this->getMemcache()->delete("user_counters_update_lock_by_user_" . $userId);
        }
    }
}