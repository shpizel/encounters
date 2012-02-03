<?php
namespace Mamba\EncountersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CronScriptCommand
 *
 * @package EncountersBundle
 */
abstract class CronScript extends ContainerAwareCommand {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Default script description",

        /**
         * Дефолтное значение номера копии
         *
         * @var int
         */
        DEFAULT_COPY_NUMBER = 1,

        /**
         * Дефолтное значение количества итераций
         *
         * @var int
         */
        DEFAULT_ITERATIONS_COUNT = 100
    ;

    protected

        /**
         * Имя скрипта
         *
         * @var string
         */
        $scriptName
    ;

    /**
     * Конфигурирование крон-скрипта
     *
     *
     */
    protected function configure() {
        $className = get_called_class();
        if (preg_match("!\\\(\w+)Command!i", $className, $scriptName)) {
            $scriptName = array_pop($scriptName);
        } else {
            $scriptName = explode("\\", $className);
            $scriptName = array_pop($scriptName);
        }

        $this
            ->setName($scriptName)
            ->setDescription(static::SCRIPT_DESCRIPTION)
            ->addOption('copy', null, InputOption::VALUE_OPTIONAL, 'Number of copy', static::DEFAULT_COPY_NUMBER)
            ->addOption('iterations', null, InputOption::VALUE_OPTIONAL, 'Iterations to restart', static::DEFAULT_ITERATIONS_COUNT)
            ->addOption('daemon', null, InputOption::VALUE_OPTIONAL, 'Daemonize', 'no')
            ->addOption('debug', null, InputOption::VALUE_OPTIONAL, 'Debug', 'yes')
        ;

        $this->scriptName = $scriptName;
    }

    /**
     * Экзекутор
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return mixed
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $copy = (int) $input->getOption('copy');
        $iterations = (int) $input->getOption('iterations');
        $daemon = $input->getOption('daemon') == 'yes';
        $debug = $input->getOption('debug') == 'yes';

        if ($copy < 1) {
            throw new CronScriptException("Invalid --copy param");
        }

        if ($daemon && $debug) {
            throw new CronScriptException("Could not start daemon with debug");
        }

        list($this->input, $this->output, $this->copy, $this->iterations, $this->daemon, $this->debug)
            = array($input, $output, $copy, $iterations, $daemon, $debug);

        if ($this->daemon) {

            if ($pid = pcntl_fork() == 0) {

                chdir("/");
                umask(0);

                if (posix_setsid() == -1) {
                    exit(-1);
                }

                if ($pid = pcntl_fork() == 0) {

                    pcntl_signal(SIGINT, $exit = function() {exit(1);});
                    pcntl_signal(SIGTERM, $exit);
                    pcntl_signal(SIGHUP, $exit);

                    if (!$this->hasAnotherInstances()) {
                        $this->process();

                        fclose($this->lockFilePointer);
                        unlink($this->lockFileName);
                    }
                } else {
                    exit;
                }
            } else {
                exit;
            }
        } else {
            if (!$this->hasAnotherInstances()) {
                $this->process();

                fclose($this->lockFilePointer);
                unlink($this->lockFileName);
            }
        }
    }

    /**
     * Возвращает результат проверки наличия запущенного инстанса скрипта с учетом копии
     *
     * @return bool
     */
    protected function hasAnotherInstances() {
        $this->lockFileName = "/tmp/" . $this->scriptName . "." . $this->copy .  ".lock";

        if ($this->lockFilePointer = @fopen($this->lockFileName, 'w')) {
            return !flock($this->lockFilePointer, LOCK_EX | LOCK_NB);
        }

        throw new CronScriptException("Unable to open lock file: " . $this->lockFileName);
    }

    /**
     * Логгер
     *
     * @return null
     */
    protected function log($message) {
        if ($this->debug) {
            echo "[" . date("H:i:s") . "]" . trim($message) . PHP_EOL;
        }
    }

    /**
     * Процессор, имплементируемый в потомках
     *
     * @abstract
     */
    abstract protected function process();
}

/**
 * CronScriptException
 *
 * @package Encounters
 */
class CronScriptException extends \Exception {

}