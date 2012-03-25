<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\CronScript;
use Mamba\EncountersBundle\EncountersBundle;

/**
 * EnergyUpdateCommand
 *
 * @package EncountersBundle
 */
class DatabaseEnergyUpdateCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Updates users energies",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:database:energy:update",

        /**
         * SQL-запрос обновления таблицы энергий
         *
         * @var str
         */
        SQL_ENERGY_UPDATE = "
            INSERT INTO
                Encounters.Energy
            SET
                `user_id` = :user_id,
                `energy`  = :energy
            ON DUPLICATE KEY UPDATE
                `energy` = :energy
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
        $worker->addFunction(EncountersBundle::GEARMAN_DATABASE_ENERGY_UPDATE_FUNCTION_NAME, function($job) use($class) {
            try {
                return $class->updateEnergy($job);
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
     * Обновление таблицы энергий
     *
     * @param $job
     */
    public function updateEnergy($job) {
        list($userId, $energy) = array_values(unserialize($job->workload()));

        $stmt = $this->getEntityManager()->getConnection()->prepare(self::SQL_ENERGY_UPDATE);
        $stmt->bindValue('user_id', $userId);
        $stmt->bindValue('energy', $energy = $this->getEnergyObject()->get($userId));

        $result = $stmt->execute();
        $this->getMemcache()->delete("energy_update_lock_by_user_" . $userId);
        if (!$result) {
            throw new CronScriptException('Unable to store data to DB.');
        }
    }
}