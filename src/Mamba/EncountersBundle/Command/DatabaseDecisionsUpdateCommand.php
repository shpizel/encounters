<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\CronScript;
use Mamba\EncountersBundle\EncountersBundle;

/**
 * DatabaseDecisionsUpdateCommand
 *
 * @package EncountersBundle
 */
class DatabaseDecisionsUpdateCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Sync decisions with database",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:database:decisions:update",

        /**
         * SQL-запрос для добавления ответа в базу
         *
         * @var str
         */
        SQL_UPDATE_DECISION = "
            INSERT INTO
                Encounters.Decisions
            SET
                `web_user_id` = :web_user_id,
                `current_user_id` = :current_user_id,
                `decision` = :decision,
                `changed` = :changed
            ON DUPLICATE KEY UPDATE
                `decision` = :decision,
                `changed` = :changed
        "
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $worker = $this->getGearman()->getWorker();
        $worker->setTimeout(static::GEARMAN_WORKER_TIMEOUT);

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_DATABASE_DECISIONS_UPDATE_FUNCTION_NAME, function($job) use($class) {
            try {
                return $class->processDecisions($job);
            } catch (\Exception $e) {
                $class->log($e->getCode() . ": " . $e->getMessage(), 16);
                throw $e;
            }
        });

        while
        (
            !$this->getMemcache()->get("cron:stop") &&
            ((time() - $this->started < $this->lifetime) || !$this->lifetime) &&
            ((memory_get_usage() < $this->memory) || !$this->memory) &&
            --$this->iterations &&
            (@$worker->work() || $worker->returnCode() == GEARMAN_TIMEOUT)
        ) {
            $this->log("Iterations: {$this->iterations}", 64);
            if ($worker->returnCode() == GEARMAN_TIMEOUT) {
                $this->log("Timed out", 48);
                continue;
            } elseif ($worker->returnCode() != GEARMAN_SUCCESS) {
                $this->log("Failed", 16);
                break;
            } elseif ($worker->returnCode() == GEARMAN_SUCCESS) {
                $this->log("Success", 64);
            }
        }

        $this->log("Bye", 48);
    }

    /**
     * Обновляет базу данных
     *
     * @param $job
     */
    public function processDecisions($job) {
        list($webUserId, $currentUserId, $decision, $time) = array_values(unserialize($job->workload()));

        if (!$time) {
            $time = time();
        }

        $stmt = $this->getEntityManager()->getConnection()->prepare(self::SQL_UPDATE_DECISION);
        $stmt->bindParam('web_user_id', $webUserId);
        $stmt->bindParam('current_user_id', $currentUserId);
        $stmt->bindParam('decision', $decision);
        $stmt->bindParam('changed', $time);

        $result = $stmt->execute();
        if (!$result) {
            throw new CronScriptException('Unable to store data to DB.');
        }
    }
}
