<?php
namespace Core\ScriptBundle;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CronScript
 *
 * @package ScriptBundle
 */
abstract class CronScript extends Script {

    const

        /**
         * Дефолтное значение количества итераций
         *
         * @var int
         */
        DEFAULT_ITERATIONS_COUNT = 100,

        /**
         * Дефолтное значение максимальной используемой памяти
         *
         * @var int
         */
        DEFAULT_MEMORY_LIMIT = 0 /** NOLIMIT */,

        /**
         * Дефолтное значение максимальной времени исполнения
         *
         * @var int
         */
        DEFAULT_LIFETIME = 0 /* undead */
    ;

    /**
     * Конфигурирование крон-скрипта
     *
     *
     */
    protected function configure() {
        $this
            ->setName($this->scriptName = static::SCRIPT_NAME)
            ->setDescription(static::SCRIPT_DESCRIPTION)
            ->addOption('copy', null, InputOption::VALUE_OPTIONAL, 'Number of copy', static::DEFAULT_COPY_NUMBER)
            ->addOption('iterations', null, InputOption::VALUE_OPTIONAL, 'Iterations to restart', static::DEFAULT_ITERATIONS_COUNT)
            ->addOption('memory', null, InputOption::VALUE_OPTIONAL, 'Memory limit', static::DEFAULT_MEMORY_LIMIT)
            ->addOption('lifetime', null, InputOption::VALUE_OPTIONAL, 'Lifetime', static::DEFAULT_LIFETIME)
            ->addOption('daemon', null, InputOption::VALUE_OPTIONAL, 'Daemonize', 'no')
            ->addOption('debug', null, InputOption::VALUE_OPTIONAL, 'Debug', 'yes')
        ;
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
        $memory = (int) $input->getOption('memory');
        $lifetime = (int) $input->getOption('lifetime');
        $daemon = $input->getOption('daemon') == 'yes';
        $debug = $input->getOption('debug') == 'yes';

        if ($copy < 1) {
            throw new CronScriptException("Invalid --copy param");
        }

        if ($memory < 0) {
            throw new CronScriptException("Invalid --memory param");
        }

        if ($lifetime < 0) {
            throw new CronScriptException("Invalid --lifetime param");
        }

        if ($daemon && $debug) {
            throw new CronScriptException("Could not start daemon with debug");
        }

        list($this->input, $this->output, $this->copy, $this->memory, $this->lifetime, $this->iterations, $this->daemon, $this->debug)
            = array($input, $output, $copy, $memory, $lifetime, $iterations, $daemon, $debug);

        $this->started = time();

        if ($this->daemon) {

            if ($pid = pcntl_fork() == 0) {

                chdir("/");
                umask(0);

                if (posix_setsid() == -1) {
                    exit(1);
                }

                if ($pid = pcntl_fork() == 0) {

                    pcntl_signal(SIGINT, $exit = function() {
                        exit(1);
                    });
                    pcntl_signal(SIGTERM, $exit);
                    pcntl_signal(SIGHUP, $exit);

                    if (!$this->hasAnotherInstances() && (!$this->getMemcache()->get("cron:stop") || (($stopCommandTimestamp = (int)$this->getMemcache()->get("cron:stop")) && ($stopCommandTimestamp < $this->started)))) {

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
            if (!$this->hasAnotherInstances() && (!$this->getMemcache()->get("cron:stop") || (($stopCommandTimestamp = (int)$this->getMemcache()->get("cron:stop")) && ($stopCommandTimestamp < $this->started)))) {
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
}

/**
 * CronScriptException
 *
 * @package ScriptBundle
 */
class CronScriptException extends \Exception {

}