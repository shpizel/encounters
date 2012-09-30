<?php
namespace Core\RedisBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Core\ScriptBundle\Script;

/**
 * RedisMigrationCommand
 *
 * @package RedisBundle
 */
class RedisMigrationCommand extends Script {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Redis migration tool",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "redis:migrate"
    ;

    /**
     * Конфигурирование крон-скрипта
     *
     *
     */
    protected function configure() {
        parent::configure();

        $this
            ->addOption('dsn', null, InputOption::VALUE_REQUIRED, "DSN", null)
            ->addOption('dir', null, InputOption::VALUE_REQUIRED, "Output directory", getcwd() . DIRECTORY_SEPARATOR . "rdb")
        ;
    }

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {

    }
}