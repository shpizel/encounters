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
        DEFAULT_COPY_NUMBER = 1,

        /**
         * Папка логов
         *
         * @var string
         */
        LOG_DIR = "/var/log/cron/"
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
            try {
                $this->process();
            } catch (\Exception $e) {
                $this->log("Error: " . $e->getMessage(), 16);
            }

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
     * @param int $format (16 — error, 32 - question, 48 - warning, 64 - info, [-1 - \r, -2 — \n вначале)
     * @param int $options (-1 - "\r", -2 — without time, -3 - without message, only \n)
     * @return null
     */
    public function log($message, $format = 0, $options = 0) {
        $colorize = function($message, $format) {
            if ($format == 64) {
                return "<info>{$message}</info>";
            } elseif ($format == 48) {
                return "<comment>{$message}</comment>";
            } elseif ($format == 32) {
                return "<question>{$message}</question>";
            } elseif ($format == 16) {
                return "<error>{$message}</error>";
            }

            return $message;
        };

        if ($this->debug) {
            $writeFunction = ($format != -1) ? "writeln" : "write";
            $message = "[" . date("d-M-Y H:i:s") . " @ <info>" . (time() - (isset($this->started) ? $this->started : $this->started = time())) . "s</info> & <comment>" . round(memory_get_usage(true)/1024/1024, 0) . "M</comment>] " . $colorize($message, $format);
            if ($format == -1) {
                $message = "\r{$message}";
            } elseif ($format == -2) {
                $message = PHP_EOL . $message;
            }

            $this->output->$writeFunction($message);
        } else {
            $message = date("[d-M-Y H:i:s") . " @ " . (time() - (isset($this->started) ? $this->started : $this->started = time())) . "s & " . round(memory_get_usage(true)/1024/1024, 0) . "M] " . trim(strip_tags($message)) . PHP_EOL;
            error_log($message, 3, $this->getLogFilename());
        }

        return true;
    }

    /**
     * Возвращает имя файла лога скрипта
     *
     * @return str
     */
    protected function getLogFilename() {
        $filename = trim(self::LOG_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "{$this->scriptName}.{$this->copy}.log";

        return $filename;
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
