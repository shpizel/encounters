<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\CronScript;

use Mamba\EncountersBundle\EncountersBundle;

/**
 * DatabaseVariablesUpdateCommand
 *
 * @package EncountersBundle
 */
class DatabaseVariablesUpdateCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Sync variables with database",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:database:variables:update",

        /**
         * SQL-запрос для добавления переменной в базу
         *
         * @var str
         */
        SQL_UPDATE_VARIABLE = "
            INSERT INTO
                Encounters.Variables
            SET
                `user_id` = :user_id,
                `key` = :key,
                `value` = :value
            ON DUPLICATE KEY UPDATE
                `value` = :value
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
        $worker->addFunction(EncountersBundle::GEARMAN_DATABASE_VARIABLES_UPDATE_FUNCTION_NAME, function($job) use($class) {
            return $class->processVariable($job);
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
     * Обновляет базу данных
     *
     * @param $job
     */
    public function processVariable($job) {
        list($userId, $key, $value) = array_values(unserialize($job->workload()));

        $this->log("Got task for <info>user_id</info> = {$userId}, <info>key</info> = {$key}, <info>value</info> = {$value}");

        $stmt = $this->getEntityManager()->getConnection()->prepare(self::SQL_UPDATE_VARIABLE);
        $stmt->bindParam('user_id', $userId);
        $stmt->bindParam('key', $key);
        $stmt->bindParam('value', $value);

        $result = $stmt->execute();
        if (!$result) {
            throw new \Core\ScriptBundle\CronScriptException('Unable to store data to DB.');
        }
    }
}