<?php
namespace Mamba\EncountersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Mamba\EncountersBundle\Command\CronScript;
use Mamba\EncountersBundle\EncountersBundle;

use Mamba\EncountersBundle\Entity\Decisions;

/**
 * DecisionsProcessCommand
 *
 * @package EncountersBundle
 */
class DecisionsProcessCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Updates users decisions",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:database:decisions:process"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $worker = $this->getGearman()->getWorker();

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_DATABASE_DECISIONS_PROCESS_FUNCTION_NAME, function($job) use($class) {
            try {
                return $class->updateDatabase($job);
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
     * Обновляет базу данных
     *
     * @param $job
     */
    public function processDecisions($job) {
        list($webUserId, $currentUserId, $decision) = array_values(unserialize($job->workload()));

        $Decision = new Decisions();
        $Decision->setWebUserId($webUserId);
        $Decision->setCurrentUserId($currentUserId);
        $Decision->setDecision($decision);
        $Decision->setChanged(time());

        $this->getEntityManager()->persist($Decision);
        $this->getEntityManager()->flush();
    }
}