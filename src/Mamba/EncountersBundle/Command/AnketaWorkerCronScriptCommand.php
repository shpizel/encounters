<?php
namespace Mamba\EncountersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * AnketaWorkerCronScriptCommand
 *
 * @package EncountersBundle
 */
class AnketaWorkerCronScriptCommand extends CronScript {

    protected function process() {
        $worker = $this->getContainer()->get('gearman')->getWorker();

        $class = $this;
        $worker->addFunction("reverse", function($job) use($class) {
            return $class->reverse($job);
        });

        $worker->addFunction("upper", function($job) use($class) {
            return $class->upper($job);
        });

        while ($worker->work() && $this->iterations) {
            if ($worker->returnCode() != GEARMAN_SUCCESS) {
                break;
            }

            $this->iterations--;
        }
    }

    public function reverse($job) {
        $details = $job->workload();
        $handle  = $job->handle();
        $result  = strrev($details);

        var_dump($result);

        return $result;
    }

    public function upper($job) {
        $details = $job->workload();
        $handle  = $job->handle();
        $result = strtoupper($details);

        var_dump($result);

        return $result;
    }
}