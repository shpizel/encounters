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
 * SpamSenderCommand
 *
 * @package EncountersBundle
 */
class SpamSenderCommand extends QueueUpdateCronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Hitlist queue updater",

        /**
         * Лимит
         *
         * @var int
         */
        LIMIT = 25
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $worker = $this->getGearman()->getWorker();

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_HITLIST_QUEUE_UPDATE_FUNCTION_NAME, function($job) use($class) {
            try {
                return $class->updateHitlistQueue($job);
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
     * Обновляет пользовательскую очередь из хитлиста
     *
     * @param $job
     */
    public function updateHitlistQueue($job) {

    }
}