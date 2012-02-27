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
 * DatabaseUpdateCommand
 *
 * @package EncountersBundle
 */
class DatabaseUpdateCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Database update",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:database:update"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $worker = $this->getGearman()->getWorker();

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_DATABASE_UPDATE_FUNCTION_NAME, function($job) use($class) {
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
    public function updateDatabase($job) {
        $Mamba = $this->getMamba();

        list($webUserId, $currentUserId, $decision) = array_values(unserialize($job->workload()));
        if ($webUserId = (int) $webUserId) {
            $Mamba->set('oid', $webUserId);

            if (!$Mamba->getReady()) {
                $this->log("Mamba is not ready!", 16);
                return;
            }
        } else {
            throw new CronScriptException("Invalid workload");
        }

        $DecisionsItem = new Decisions();
        $DecisionsItem->setWebUserId($webUserId);
        $DecisionsItem->setCurrentUserId($currentUserId);
        $DecisionsItem->setDecision($decision);
        $DecisionsItem->setChanged(time());

        $em = $this->getContainer()->get('doctrine')->getEntityManager();
        $em->persist($DecisionsItem);
        $em->flush();
    }
}