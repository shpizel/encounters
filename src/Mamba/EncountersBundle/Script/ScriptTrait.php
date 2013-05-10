<?php
namespace Mamba\EncountersBundle\Script;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
use Core\MambaBundle\Helpers\PlatformSettings;
use Mamba\EncountersBundle\Helpers\Popularity;
use Mamba\EncountersBundle\Helpers\Notifications;
use Mamba\EncountersBundle\Helpers\Services;
use Mamba\EncountersBundle\Helpers\Purchased;
use Mamba\EncountersBundle\Helpers\Stats;
use Mamba\EncountersBundle\Helpers\Variables;
use Mamba\EncountersBundle\Helpers\Account;
use Mamba\EncountersBundle\Helpers\Photoline;
use Mamba\EncountersBundle\Helpers\Gifts;

use Mamba\EncountersBundle\Helpers\Messenger\Messages;
use Mamba\EncountersBundle\Helpers\Messenger\Contacts;

/**
 * ScriptTrait
 *
 * @package EncountersBundle
 */
trait ScriptTrait {

    /**
     * Конфигурирование крон-скрипта
     *
     *
     */
    protected function configure() {
        parent::configure();

        $this->addOption('gearman', null, InputOption::VALUE_OPTIONAL, 'Gearman DSN', null);
    }

    /**
     * Gearman client getter
     *
     * @return \GearmanClient
     */
    public function getGearmanClient() {
        if ($dsn = $this->input->getOption('gearman')) {
            return $this->getGearman()->getClient(\Core\GearmanBundle\GearmanDSN::getDSNFromString($dsn));
        }

        return $this->getGearman()->getClient();
    }

    /**
     * Gearman worker getter
     *
     * @return \GearmanWorker
     */
    public function getGearmanWorker() {
        if ($dsn = $this->input->getOption('gearman')) {
            return $this->getGearman()->getWorker(\Core\GearmanBundle\GearmanDSN::getDSNFromString($dsn));
        }

        return $this->getGearman()->getWorker();
    }

    /**
     * Purchased object getter
     *
     * @return Purchased
     */
    public function getPurchasedHelper() {
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
    public function getVariablesHelper() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new Variables($this->getContainer());
    }

    /**
     * Account object getter
     *
     * @return Account
     */
    public function getAccountHelper() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new Account($this->getContainer());
    }

    /**
     * Notifications object getter
     *
     * @return Notifications
     */
    public function getNotificationsHelper() {
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
    public function getPopularityHelper() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new Popularity($this->getContainer());
    }

    /**
     * Battery getter
     *
     * @return Battery
     */
    public function getBatteryHelper() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new Battery($this->getContainer());
    }

    /**
     * Contacts helper getter
     *
     * @return Contacts
     */
    public function getContactsHelper() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new Contacts($this->getContainer());
    }

    /**
     * Photoline getter
     *
     * @return Photoline
     */
    public function getPhotolineHelper() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new Photoline($this->getContainer());
    }

    /**
     * Energy getter
     *
     * @return Energy
     */
    public function getEnergyHelper() {
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
    public function getSearchPreferencesHelper() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new SearchPreferences($this->getContainer());
    }

    /**
     * Gifts helper getter
     *
     * @return Gifts
     */
    public function getGiftsHelper() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new Gifts($this->getContainer());
    }

    /**
     * Contacts queue getter
     *
     * @return ContactsQueue
     */
    public function getContactsQueueHelper() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new ContactsQueue($this->getContainer());
    }

    /**
     * Messages helper getter
     *
     * @return Messages
     */
    public function getMessagesHelper() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new Messages($this->getContainer());
    }

    /**
     * Current queue getter
     *
     * @return CurrentQueue
     */
    public function getCurrentQueueHelper() {
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
    public function getHitlistQueueHelper() {
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
    public function getPriorityQueueHelper() {
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
    public function getSearchQueueHelper() {
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
    public function getViewedQueueHelper() {
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
    public function getCountersHelper() {
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
    public function getStatsHelper() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new Stats($this->getContainer());
    }
}
