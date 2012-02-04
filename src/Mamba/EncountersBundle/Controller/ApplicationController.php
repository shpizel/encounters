<?php
namespace Mamba\EncountersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Mamba\EncountersBundle\Preferences;
use Mamba\EncountersBundle\Battery;
use Mamba\EncountersBundle\Energy;
use Mamba\EncountersBundle\Hitlist;
use Mamba\EncountersBundle\PlatformSettings;

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

        return self::$Instances[__FUNCTION__] = new Battery($this->getRedis(), $this->getMemcache());
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

        return self::$Instances[__FUNCTION__] = new Energy($this->getRedis(), $this->getMemcache());
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
}