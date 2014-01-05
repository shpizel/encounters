<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\CronScript;
use PDO;

use Mamba\EncountersBundle\EncountersBundle;

/**
 * DatabaseLastaccessUpdateCommand
 *
 * @package EncountersBundle
 */
class DatabaseLastaccessUpdateCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Updates users lastaccess",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:database:lastaccess:update",

        /**
         * SQL-запрос обновления таблицы UserLastAccess
         *
         * @var str
         */
        SQL_USER_LASTACCESS_UPDATE = "
            INSERT INTO
                Encounters.UserLastAccess
            SET
                `user_id`     = :user_id,
                `lastaccess` = :lastaccess
            ON DUPLICATE KEY UPDATE
                `lastaccess` = :lastaccess
        ",

        /**
         * SQL-запрос обновления таблицы UserLastOnline
         *
         * @var str
         */
        SQL_USER_LAST_ONLINE_UPDATE = "
            INSERT INTO
                Encounters.UserLastOnline
            SET
                `user_id`     = :user_id,
                `last_online` = :last_online
            ON DUPLICATE KEY UPDATE
                `last_online` = :last_online
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
        $worker->addFunction(EncountersBundle::GEARMAN_DATABASE_LASTACCESS_FUNCTION_NAME, function($job) use($class) {
            return $class->updateUserLastaccess($job);
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
    public function updateUserLastaccess($job) {
        list($userId) = array_values(unserialize($job->workload()));

        $this->log("Got task for <info>user_id</info> = {$userId}");

        $stmt = $this->getEntityManager()->getConnection()->prepare(self::SQL_USER_LASTACCESS_UPDATE);
        $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue('lastaccess', $_lastAccess = (int) $this->getVariablesHelper()->get($userId, 'lastaccess'), PDO::PARAM_INT);

        if (!($result = $stmt->execute())) {
            throw new \Core\ScriptBundle\CronScriptException('Unable to store data to UserLastAccess');
        } else {
            $stmt = $this->getEntityManager()->getConnection()->prepare(self::SQL_USER_LAST_ONLINE_UPDATE);
            $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue('last_online', $_lastOnline = (int) $this->getVariablesHelper()->get($userId, 'lastaccess'), PDO::PARAM_INT);

            if (!$result = $stmt->execute()) {
                throw new \Core\ScriptBundle\CronScriptException('Unable to store data to UserLastOnline');
            } else {
                $this->getMemcache()->delete("lastaccess_update_lock_by_user_" . $userId);
            }
        }
    }
}