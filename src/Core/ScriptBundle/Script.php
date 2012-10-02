<?php
namespace Core\ScriptBundle;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Core\GearmanBundle\Gearman;
use Core\RedisBundle\Redis;
use Core\MemcacheBundle\Memcache;
use Core\MambaBundle\API\Mamba;

/**
 * Script
 *
 * @package ScriptBundle
 */
abstract class Script extends ContainerAwareCommand {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Default script description",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = null,

        /**
         * Дефолтное значение номера копии
         *
         * @var int
         */
        DEFAULT_COPY_NUMBER = 1
    ;

    protected

        /**
         * Имя скрипта
         *
         * @var string
         */
        $scriptName
    ;

    protected static

        /**
         * Инстансы объектов
         *
         * @var array
         */
        $Instances = array()
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
        $debug = true;

        if ($copy < 1) {
            throw new ScriptException("Invalid --copy param");
        }

        list($this->input, $this->output, $this->copy, $this->debug) = array($input, $output, $copy, $debug);
        $this->started = time();

        if (!$this->hasAnotherInstances() && (!$this->getMemcache()->get("cron:stop") || (($stopCommandTimestamp = (int)$this->getMemcache()->get("cron:stop")) && ($stopCommandTimestamp < $this->started)))) {
            $this->process();

            fclose($this->lockFilePointer);
            unlink($this->lockFileName);
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

        throw new ScriptException("Unable to open lock file: " . $this->lockFileName);
    }

    /**
     * Логгер
     *
     * @param string $message
     * @param int $code (16 — error,
     * @return null
     */
    public function log($message, $code = 0) {
        $colorize = function($message, $code) {
            if ($code == 64) {
                return "<info>{$message}</info>";
            } elseif ($code == 48) {
                return "<comment>{$message}</comment>";
            } elseif ($code == 32) {
                return "<question>{$message}</question>";
            } elseif ($code == 16) {
                return "<error>{$message}</error>";
            }

            return $message;
        };

        if ($this->debug) {
            $writeFunction = ($code >= 0) ? "writeln" : "write";
            $this->output->$writeFunction((($code < 0) ? "\r" : "") . "[" . date("d/m/y H:i:s") . " @ <info>" . (time() - (isset($this->started) ? $this->started : $this->started = time())) . "s</info> & <comment>" . round(memory_get_usage(true)/1024/1024, 0) . "M</comment>] " . $colorize(trim($message), $code));
        }

        return true;
    }

    /**
     * Redis getter
     *
     * @return Redis
     */
    public function getRedis() {
        return $this->getContainer()->get('redis');
    }

    /**
     * Memcache getter
     *
     * @return Memcache
     */
    public function getMemcache() {
        return $this->getContainer()->get('memcache');
    }

    /**
     * Mamba getter
     *
     * @return Mamba
     */
    public function getMamba() {
        return $this->getContainer()->get('mamba');
    }

    /**
     * Gearman getter
     *
     * @return \Core\GearmanBundle\Gearman
     */
    public function getGearman() {
        return $this->getContainer()->get('gearman');
    }

    /**
     * Doctrine getter
     *
     * @return Doctrine
     */
    public function getDoctrine() {
        return $this->getContainer()->get('doctrine');
    }

    /**
     * Servers getter
     *
     * @return Servers
     */
    public function getServers() {
        return $this->getContainer()->get('servers');
    }

    /**
     * Entity Manager getter
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager() {
        return $this->getDoctrine()->getEntityManager();
    }

    /**
     * Процессор, имплементируемый в потомках
     *
     * @abstract
     */
    abstract protected function process();
}

/**
 * ScriptException
 *
 * @package ScriptBundle
 */
class ScriptException extends \Exception {

}
