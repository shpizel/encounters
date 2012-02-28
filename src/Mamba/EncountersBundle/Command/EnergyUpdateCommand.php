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
class EnergyUpdateCommand extends CronScript {

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
        SCRIPT_NAME = "cron:database:energy:update"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $worker = $this->getGearman()->getWorker();

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_DATABASE_ENERGY_PROCESS_FUNCTION_NAME, function($job) use($class) {
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
        if ($Energy = $this->getEntityManager()->getRepository('EncountersBundle:Energy')->find($userId)) {
            $Energy->setEnergy($energy);

            $this->getEntityManager()->flush();
        } else {
            $Energy = new Energy();
            $Energy->setUserId($userId);
            $Energy->setEnergy($energy);

            $this->getEntityManager()->persist($Energy);
            $this->getEntityManager()->flush();
        }
    }
}