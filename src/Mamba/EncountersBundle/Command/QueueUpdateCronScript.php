<?php
namespace Mamba\EncountersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Mamba\PlatformBundle\API\Mamba;
use Mamba\EncountersBundle\Battery;
use Mamba\EncountersBundle\Energy;
use Mamba\EncountersBundle\Hitlist;
use Mamba\EncountersBundle\Preferences;

use Mamba\RedisBundle\Redis;
use Mamba\MemcacheBundle\Memcache;

/**
 * QueueUpdateCronScript
 *
 * @package EncountersBundle
 */
abstract class QueueUpdateCronScript extends CronScript {

    private static

        /**
         * Инстансы объектов
         *
         * @var array
         */
        $Instances = array()
    ;

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
     * @return Gearman
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


}