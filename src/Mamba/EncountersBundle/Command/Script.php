<?php
namespace Mamba\EncountersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Mamba\PlatformBundle\API\Mamba;

use Mamba\RedisBundle\Redis;
use Mamba\MemcacheBundle\Memcache;

use Mamba\EncountersBundle\Helpers\Queues\ContactsQueue;
use Mamba\EncountersBundle\Helpers\Queues\CurrentQueue;
use Mamba\EncountersBundle\Helpers\Queues\HitlistQueue;
use Mamba\EncountersBundle\Helpers\Queues\PriorityQueue;
use Mamba\EncountersBundle\Helpers\Queues\SearchQueue;
use Mamba\EncountersBundle\Helpers\Queues\ViewedQueue;

use Mamba\EncountersBundle\Helpers\SearchPreferences;
use Mamba\EncountersBundle\Helpers\Battery;
use Mamba\EncountersBundle\Helpers\Energy;
use Mamba\EncountersBundle\Helpers\Counters;
use Mamba\EncountersBundle\Helpers\PlatformSettings;
use Mamba\EncountersBundle\Helpers\Popularity;
use Mamba\EncountersBundle\Helpers\Notifications;
use Mamba\EncountersBundle\Helpers\Services;
use Mamba\EncountersBundle\Helpers\Purchased;
use Mamba\EncountersBundle\Helpers\Stats;
use Mamba\EncountersBundle\Helpers\Variables;

/**
 * CronScriptCommand
 *
 * @package EncountersBundle
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
         *
         *
         * @var int
         */
        GEARMAN_CLIENT_TIMEOUT_DEFAULT = 1000,

        /**
         *
         *
         * @var int
         */
        GEARMAN_WORKER_TIMEOUT_DEFAULT = 5000
    ;

    protected

        /**
         * Имя скрипта
         *
         * @var string
         */
        $scriptName
    ;

    private static

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

        $this->getGearman()->getClient()->setTimeout(self::GEARMAN_CLIENT_TIMEOUT_DEFAULT);
        $this->getGearman()->getWorker()->setTimeout(self::GEARMAN_WORKER_TIMEOUT_DEFAULT);

        if (!$this->hasAnotherInstances() && !$this->getMemcache()->get("cron:stop")) {
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
            $this->output->writeln("[" . date("d.m.y H:i:s") . "] " . $colorize(trim($message), $code));
        }
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
     * @return \Mamba\GearmanBundle\Gearman
     */
    public function getGearman() {
        return $this->getContainer()->get('gearman');
    }

    /**
     * Battery getter
     *
     * @return Battery
     */
    public function getBatteryObject() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new Battery($this->getContainer());
    }

    /**
     * Energy getter
     *
     * @return Energy
     */
    public function getEnergyObject() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new Energy($this->getContainer());
    }

    /**
     * Search preferences getter
     *
     * @return SearchPreferences
     */
    public function getSearchPreferencesObject() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new SearchPreferences($this->getContainer());
    }

    /**
     * Contacts queue getter
     *
     * @return ContactsQueue
     */
    public function getContactsQueueObject() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new ContactsQueue($this->getContainer());
    }

    /**
     * Current queue getter
     *
     * @return CurrentQueue
     */
    public function getCurrentQueueObject() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new CurrentQueue($this->getContainer());
    }

    /**
     * Hitlist queue getter
     *
     * @return HitlistQueue
     */
    public function getHitlistQueueObject() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new HitlistQueue($this->getContainer());
    }

    /**
     * Priority queue getter
     *
     * @return PriorityQueue
     */
    public function getPriorityQueueObject() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new PriorityQueue($this->getContainer());
    }

    /**
     * Search queue getter
     *
     * @return SearchQueue
     */
    public function getSearchQueueObject() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new SearchQueue($this->getContainer());
    }

    /**
     * Viewed queue getter
     *
     * @return ViewedQueue
     */
    public function getViewedQueueObject() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new ViewedQueue($this->getContainer());
    }

    /**
     * Counters object getter
     *
     * @return Counters
     */
    public function getCountersObject() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new Counters($this->getContainer());
    }

    /**
     * Stats object getter
     *
     * @return Stats
     */
    public function getStatsObject() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new Stats($this->getContainer());
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
     * Entity Manager getter
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager() {
        return $this->getDoctrine()->getEntityManager();
    }

    /**
     * Purchased object getter
     *
     * @return Purchased
     */
    public function getPurchasedObject() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new Purchased($this->getContainer());
    }

    /**
     * Variables object getter
     *
     * @return Variables
     */
    public function getVariablesObject() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new Variables($this->getContainer());
    }

    /**
     * Notifications object getter
     *
     * @return Notifications
     */
    public function getNotificationsObject() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new Notifications($this->getContainer());
    }

    /**
     * Popularity object getter
     *
     * @return Popularity
     */
    public function getPopularityObject() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new Popularity($this->getContainer());
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
 * @package Encounters
 */
class ScriptException extends \Exception {

}
