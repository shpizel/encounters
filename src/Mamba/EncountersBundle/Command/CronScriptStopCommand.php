<?php
namespace Mamba\EncountersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Mamba\EncountersBundle\Command\CronScript;

/**
 * CronScriptStopCommand
 *
 * @package EncountersBundle
 */
class CronScriptStopCommand extends ContainerAwareCommand {

    /**
     * Конфигурирование крон-скрипта
     *
     *
     */
    protected function configure() {
        $this->setName('cron:stop')->setDescription("Stops cron scripts");
    }

    /**
     * Экзекутор
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return mixed
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $Memcache = $this->getContainer()->get('memcache');
        if ($crons = $this->getCronScriptsList()) {
            $Memcache->add("cron:stop", time(), 3600);

            $timeout = 0;
            while ($crons = $this->getCronScriptsList()) {
                $output->writeln("Waiting for " . implode(", ", $crons));
                sleep(1);
                $timeout++;

                if ($timeout > 5*60) {
                    exec("kill -9 " . implode(" ", $crons));
                }
            }

            $Memcache->delete("cron:stop");
            $output->writeln("<info>OK</info>");
        } else {
            $output->writeln("<error>No cron scripts was found!</error>");
        }
    }

    /**
     * Возвращает список уже запущенных кронов
     */
    private function getCronScriptsList() {
        $result = array();
        exec('ps ax | grep php | grep "cron:" | grep -v "cron:stop"', $result);
        array_filter($result, function($item) {
            return (bool) preg_match("!console cron:\w+!i", $item);
        });

        return
            array_filter(
                array_map(function($item) {
                    $item = explode(" ", $item);
                    return (int) array_shift($item);
                }, $result),
                function($item) {
                    return (bool) $item;
                }
            )
        ;
    }
}
