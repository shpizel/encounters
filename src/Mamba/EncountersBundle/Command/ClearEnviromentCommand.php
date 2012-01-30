<?php
namespace Mamba\EncountersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ClearEnviromentCommand
 *
 * @package EncountersBundle
 */
class ClearEnviromentCommand extends ContainerAwareCommand {

    /**
     * Конфигурирование крон-скрипта
     *
     *
     */
    protected function configure() {
        $this->setName('ClearEnviroment');
    }

    /**
     * Экзекутор
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return mixed
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        if (posix_getuid()) {
            throw new \LogicException("This script should be run from super user!");
        }

        /** Чистим redis */
        $this->getContainer()->get('redis')->flushAll();

        /** Чистим php crons */
        system("killall php");

        /** Чистим базу */
    }
}