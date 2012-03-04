<?php
namespace Mamba\EncountersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Mamba\EncountersBundle\Helpers\SearchPreferences;
use Mamba\EncountersBundle\Helpers\Battery;
use Mamba\EncountersBundle\Helpers\Energy;
use Mamba\EncountersBundle\Helpers\Hitlist;
use Mamba\EncountersBundle\Helpers\Counters;
use Mamba\EncountersBundle\Helpers\PlatformSettings;
use Mamba\EncountersBundle\Helpers\Popularity;
use Mamba\EncountersBundle\Helpers\Notifications;
use Mamba\EncountersBundle\Helpers\Services;
use Mamba\EncountersBundle\Helpers\Purchased;
use Mamba\EncountersBundle\Helpers\Stats;

use Mamba\PlatformBundle\API\Mamba;
use Mamba\GearmanBundle\Gearman;
use Symfony\Component\HttpFoundation\Session;

use Mamba\EncountersBundle\Helpers\Queues\ContactsQueue;
use Mamba\EncountersBundle\Helpers\Queues\CurrentQueue;
use Mamba\EncountersBundle\Helpers\Queues\HitlistQueue;
use Mamba\EncountersBundle\Helpers\Queues\PriorityQueue;
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

        return self::$Instances[__FUNCTION__] = new Battery($this->container);
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

        return self::$Instances[__FUNCTION__] = new Energy($this->container);
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

        return self::$Instances[__FUNCTION__] = new Hitlist($this->container);
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

        return self::$Instances[__FUNCTION__] = new SearchPreferences($this->container);
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

        return self::$Instances[__FUNCTION__] = new ContactsQueue($this->container);
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

        return self::$Instances[__FUNCTION__] = new CurrentQueue($this->container);
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

        return self::$Instances[__FUNCTION__] = new HitlistQueue($this->container);
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

        return self::$Instances[__FUNCTION__] = new PriorityQueue($this->container);
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

        return self::$Instances[__FUNCTION__] = new SearchQueue($this->container);
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

        return self::$Instances[__FUNCTION__] = new ViewedQueue($this->container);
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

        return self::$Instances[__FUNCTION__] = new Counters($this->container);
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

        return self::$Instances[__FUNCTION__] = new Notifications($this->container);
    }

    /**
     * Services object getter
     *
     * @return Services
     */
    public function getServicesObject() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new Services($this->container);
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

        return self::$Instances[__FUNCTION__] = new Purchased($this->container);
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

        return self::$Instances[__FUNCTION__] = new Stats($this->container);
    }

    /**
     * Возвращает массив данных, общих по всему приложению
     *
     * @return array
     */
    public function getInitialData() {
        $dataArray = array();
        $dataArray['settings'] = array(
            'platform' => json_encode($platformSettings = $this->getPlatformSettingsObject()->get($webUserId = (int) $this->getMamba()->get('oid'))),
            'search'   => json_encode($searchPreferences = $this->getSearchPreferencesObject()->get($webUserId))
        );

        $dataArray['platform'] = $platformSettings;

        $webUser = $this->getMamba()->Anketa()->getInfo($webUserId);
        $dataArray['webuser'] = array(
            'anketa'      => $webUser[0],
            'popularity'  => $this->getPopularity(),
            'battery'     => $this->getBatteryObject()->get($webUserId),
            'preferences' => $searchPreferences,
            'stats'       => array(
                'mychoice' => $this->getCountersObject()->get($webUserId, 'mychoice'),
                'visited'  => $this->getCountersObject()->get($webUserId, 'visited'),
                'mutual'   => $this->getCountersObject()->get($webUserId, 'mutual'),
            ),
        );

        $dataArray['webuser']['json'] = json_encode($dataArray['webuser']);
        $dataArray['routes'] = json_encode($this->getRoutes());

        $dataArray['notification'] = array(
            'message' => $this->getNotificationsObject()->get($webUserId),
        );

        $dataArray['controller'] = strtolower($this->getControllerName(get_called_class()));
        return $dataArray;
    }

    /**
     * Возвращает имя контроллера по имени класса
     *
     * @return str
     */
    private function getControllerName($className) {
        $className = explode("\\", $className);
        $className = array_pop($className);
        return str_replace("Controller", "", $className);
    }

    /**
     * Определитель популярности
     *
     * @return array
     */
    private function getPopularity() {
        $webUserId = (int) $this->getMamba()->get('oid');
        $popularity = Popularity::getPopularity($this->getEnergyObject()->get($webUserId));

        $dataArray = array('title' => 'Низкая', 'class' => 'low');
        if ($popularity < 4) {
            $dataArray['title'] = 'Низкая';
            $dataArray['class'] = 'low';
        } elseif ($popularity >= 4 && $popularity < 8) {
            $dataArray['title'] = 'Средняя';
            $dataArray['class'] = 'normal';
        } elseif ($popularity >= 8 && $popularity < 12) {
            $dataArray['title'] = 'Высокая';
            $dataArray['class'] = 'high';
        } elseif ($popularity >= 12) {
            $dataArray['title'] = 'Супер';
            $dataArray['class'] = 'super';
        }

        return $dataArray;
    }

    /**
     * Возвращает роутинг приложения в виде ассоциативного массива
     *
     * @return array
     */
    public function getRoutes() {
        $routes = array();

        $router = $this->get('router');
        foreach ($router->getRouteCollection()->all() as $name => $route) {
            $routes[$name] = $route->compile()->getPattern();
        }

        return $routes;
    }
}