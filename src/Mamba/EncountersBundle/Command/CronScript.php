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
 * @package Encounters
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
        DEFAULT_ITERATIONS_COUNT = 500
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
            ->setDescription(self::SCRIPT_DESCRIPTION)
            ->addOption('copy', null, InputOption::VALUE_OPTIONAL, 'Number of copy', self::DEFAULT_COPY_NUMBER)
            ->addOption('iterations', null, InputOption::VALUE_OPTIONAL, 'Iterations to restart', self::DEFAULT_ITERATIONS_COUNT)
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
        list($this->input, $this->output, $this->copy, $this->iterations) = array(
            $input,
            $output,
            (int) $input->getOption('copy'),
            (int) $input->getOption('iterations')
        );

        if (!$this->hasAnotherInstances()) {
            $this->process();
        }
    }

    /**
     * Возвращает результат проверки наличия запущенного инстанса скрипта с учетом копии
     *
     * @return bool
     */
    protected function hasAnotherInstances() {
        $lockFileName = "/tmp/" . $this->scriptName . "." . $this->copy .  ".lock";
        if ($this->lockFilePointer = @fopen($lockFileName, 'w')) {
            return !flock($this->lockFilePointer, LOCK_EX | LOCK_NB);
        }
    }

    /**
     * Процессор, имплементируемый в потомках
     *
     * @abstract
     */
    abstract protected function process();
}