<?php
namespace Mamba\EncountersBundle\Command;

use Core\ScriptBundle\CronScriptException;
use Mamba\EncountersBundle\Helpers\Users;
use Mamba\EncountersBundle\Script\CronScript;

use Mamba\EncountersBundle\EncountersBundle;

/**
 * DatabaseUsersSearchPreferencesUpdateCommand
 *
 * @package EncountersBundle
 */
class DatabaseUsersSearchPreferencesUpdateCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Updates users",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:database:users:search:preferences:update",

        /**
         * SQL-запрос
         *
         * @var str
         */
        SQL_INSERT_INTO_USER_SEARCH_PREFERENCES = "
            INSERT INTO
                UserSearchPreferences
            SET
                `user_id`  = :user_id,
                `gender`   = :gender,
                `age_from` = :age_from,
                `age_to`   = :age_to
            ON DUPLICATE KEY UPDATE
                `gender`   = :gender,
                `age_from` = :age_from,
                `age_to`   = :age_to
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
        $worker->addFunction(EncountersBundle::GEARMAN_DATABASE_USERS_SEARCH_PREFERENCES_UPDATE_FUNCTION_NAME, function($job) use($class) {
            return $class->updateUsersSearchPreferences($job);
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
     * Обновление таблицы энергий
     *
     * @param $job
     */
    public function updateUsersSearchPreferences($job) {
        list($userId, ) = array_values(unserialize($job->workload()));

        $this->log("Got task for <info>{$userId}</info> user");

        if ($searchPreferences = $this->getSearchPreferencesHelper()->get($userId)) {
            return
                $this
                    ->getMySQL()
                        ->getQuery(self::SQL_INSERT_INTO_USER_SEARCH_PREFERENCES)
                        ->bindArray([
                            ['user_id', (int) $userId],
                            ['gender', $searchPreferences['gender']],
                            ['age_from', (int) $searchPreferences['age_from']],
                            ['age_to', (int) $searchPreferences['age_to']],
                        ])
                        ->execute()
                        ->getResult()
            ;
        }
    }
}
