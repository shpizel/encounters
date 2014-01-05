<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\CronScript;

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
                Encounters.UserEnergy
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
        $worker = $this->getGearmanWorker();

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_DATABASE_ENERGY_UPDATE_FUNCTION_NAME, function($job) use($class) {
            return $class->updateEnergy($job);
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
    public function updateEnergy($job) {
        list($userId, $energy) = array_values(unserialize($job->workload()));

        $this->log("Got task for <info>current_user_id</info> = {$userId}, <info>energy</info> = {$energy}");

        if ($this->getSearchPreferencesHelper()->exists($userId)) {
            $stmt = $this->getEntityManager()->getConnection()->prepare(self::SQL_ENERGY_UPDATE);
            $stmt->bindValue('user_id', $userId);
            $stmt->bindValue('energy', $energy = $this->getEnergyHelper()->get($userId));

            $result = $stmt->execute();
            $this->getMemcache()->delete("energy_update_lock_by_user_" . $userId);
            if (!$result) {
                throw new \Core\ScriptBundle\CronScriptException('Unable to store data to DB.');
            }
        }
    }
}