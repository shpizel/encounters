<?php
namespace Mamba\EncountersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Mamba\EncountersBundle\Command\CronScript;
use Mamba\EncountersBundle\EncountersBundle;

use Mamba\EncountersBundle\Entity\Energy;

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

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_DATABASE_ENERGY_UPDATE_FUNCTION_NAME, function($job) use($class) {
            try {
                return $class->updateEnergy($job);
            } catch (\Exception $e) {
                $class->log($e->getCode() . ": " . $e->getMessage(), 16);
                return;
            }
        });

        $this->log("Iterations: {$this->iterations}", 64);
        while ($worker->work() && --$this->iterations && !$this->getMemcache()->get("cron:stop")) {
            $this->log("Iterations: {$this->iterations}", 64);

            if ($worker->returnCode() != GEARMAN_SUCCESS) {
                break;
            }
        }
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
        $stmt->execute();

        $this->getMemcache()->delete("energy_update_lock_by_user_" . $userId);
    }
}