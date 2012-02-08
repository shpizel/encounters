<?php
namespace Mamba\EncountersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Mamba\EncountersBundle\Helpers\Preferences;
use Mamba\EncountersBundle\Helpers\Battery;
use Mamba\EncountersBundle\Helpers\Energy;
use Mamba\EncountersBundle\Helpers\Hitlist;
use Mamba\EncountersBundle\Helpers\PlatformSettings;

use Mamba\EncountersBundle\Helpers\Queues\ContactsQueue;
use Mamba\EncountersBundle\Helpers\Queues\CurrentQueue;
use Mamba\EncountersBundle\Helpers\Queues\HitlistQueue;
use Mamba\EncountersBundle\Helpers\Queues\PriorityQueue;
use Mamba\EncountersBundle\Helpers\Queues\ReverseQueue;
use Mamba\EncountersBundle\Helpers\Queues\SearchQueue;
use Mamba\EncountersBundle\Helpers\Queues\ViewedQueue;

/**
 * ApplicationController
 *
 * @package EncountersBundle
 */
abstract class ApplicationController extends Controller {

    protected static

        /**
         * Инстансы объектов
         *
         * @var array
         */
        $Instances = array()
    ;

    /**
     * Session getter
     *
     * @return Session
     */
    public function getSession() {
        return $this->get('session');
    }

    /**
     * Mamba getter
     *
     * @return Mamba
     */
    public function getMamba() {
        return $this->get('mamba');
    }

    /**
     * Memcache getter
     *
     * @return Memcache
     */
    public function getMemcache() {
        return $this->get('memcache');
    }

    /**
     * Redis getter
     *
     * @return Redis
     */
    public function getRedis() {
        return $this->get('redis');
    }

    /**
     * Gearman getter
     *
     * @return Gearman
     */
    public function getGearman() {
        return $this->get('gearman');
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

        return self::$Instances[__FUNCTION__] = new Battery($this->getRedis());
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

        return self::$Instances[__FUNCTION__] = new Energy($this->getRedis());
    }

    /**
     * Hitlist getter
     *
     * @return Hitlist
     */
    public function getHitlistObject() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new Hitlist($this->getRedis());
    }

    /**
     * Preferences getter
     *
     * @return Preferences
     */
    public function getPreferencesObject() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new Preferences($this->getRedis());
    }

    /**
     * Platform settings getter
     *
     * @return PlatformSettings
     */
    public function getPlatformSettingsObject() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new PlatformSettings($this->getRedis());
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

        return self::$Instances[__FUNCTION__] = new ContactsQueue($this->getRedis());
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

        return self::$Instances[__FUNCTION__] = new CurrentQueue($this->getRedis());
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

        return self::$Instances[__FUNCTION__] = new HitlistQueue($this->getRedis());
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

        return self::$Instances[__FUNCTION__] = new PriorityQueue($this->getRedis());
    }

    /**
     * Reverse queue getter
     *
     * @return ReverseQueue
     */
    public function getReverseQueueObject() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new ReverseQueue($this->getRedis());
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

        return self::$Instances[__FUNCTION__] = new SearchQueue($this->getRedis());
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

        return self::$Instances[__FUNCTION__] = new ViewedQueue($this->getRedis());
    }

    /**
     * Возвращает массив данных, общих по всему приложению
     *
     * @return array
     */
    public function getInitialData() {
        $data = array(
            'settings' => array(),
            'webuser'  => array(),
            'stats'    => array(),
        );

        $data['settings']['platform'] = json_encode($this->getPlatformSettingsObject()->get($webUserId = (int) $this->getMamba()->get('oid')));
        $data['settings']['search']   = json_encode($preferences = $this->getPreferencesObject()->get($webUserId));

        $data['who'] = array(
            'instrumental' => $preferences['gender'] == 'F' ? 'ней' : 'ним',
            'nominative' => $preferences['gender'] == 'F' ? 'она' : 'он'
        );

        $data['stats']['charge']   = $this->getBatteryObject()->get($webUserId);
        $data['stats']['mychoice'] = 10;
        $data['stats']['visitors'] = 10;
        $data['stats']['mutual']   = 10;

        $data['webuser']['popularity'] = array('title'=>'Низкая', 'class'=>'high');
        $data['webuser']['battery'] = array('charge'=>2);
        $data['webuser']['stats'] = array(
            'visitors' => 1,
            'mutual' => 1,
            'mychoice' => 10,
        );

        return $data;
    }
}