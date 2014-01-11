<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\CronScript;
use PDO;

use Mamba\EncountersBundle\EncountersBundle;

/**
 * DatabaseUserTrafficSourcesUpdateCommand
 *
 * @package EncountersBundle
 */
class DatabaseUserTrafficSourcesUpdateCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Update users traffic sources",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:database:user:traffic:sources:update"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $worker = $this->getGearmanWorker();

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_DATABASE_USERS_TRAFFIC_SOURCES_UPDATE_FUNCTION_NAME, function($job) use($class) {
            return $class->updateUserTrafficSources($job);
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
    public function updateUserTrafficSources($job) {
        list($userId, $source, ) = array_values(unserialize($job->workload()));

        $this->log("Got task for <info>user_id</info> = {$userId}");

        $Query = $this->getMySQL()->getQuery("
            INSERT INTO
                `Encounters`.`UserTrafficSources`
            SET
                `user_id`              = :user_id,
                `from_{$source}_count` = 1,
                `last_from_{$source}`  = NOW()
            ON DUPLICATE KEY UPDATE
                `from_{$source}_count` = `from_{$source}_count` + 1,
                `last_from_{$source}`  = NOW()
        ")->bind('user_id', $userId, PDO::PARAM_INT);

        if (!($result = $Query->execute()->getResult())) {
            throw new \Core\ScriptBundle\CronScriptException('Unable to store data to UserTrafficSources');
        }
    }
}