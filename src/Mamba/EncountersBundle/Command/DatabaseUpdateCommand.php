<?php
namespace Mamba\EncountersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Mamba\EncountersBundle\Command\QueueUpdateCronScript;
use Mamba\EncountersBundle\EncountersBundle;

/**
 * DatabaseUpdateCommand
 *
 * @package EncountersBundle
 */
class DatabaseUpdateCommand extends QueueUpdateCronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Database updater"
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
        while ($worker->work() && --$this->iterations) {
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

    }
}