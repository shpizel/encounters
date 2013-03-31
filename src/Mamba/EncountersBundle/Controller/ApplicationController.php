<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Helpers\Gifts;
use Mamba\EncountersBundle\Helpers\Messenger\Contacts;
use Mamba\EncountersBundle\Helpers\Messenger\Messages;
use Mamba\EncountersBundle\Tools\Gifts\Gift;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

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

use Core\MambaBundle\API\Mamba;
use Core\GearmanBundle\Gearman;
use Core\ServersBundle\Servers;
use Core\RedisBundle\Redis;
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
     * Servers getter
     *
     * @return Servers
     */
    public function getServers() {
        return $this->get('servers');
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
     * Photoline getter
     *
     * @return Photoline
     */
    public function getPhotolineObject() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new Photoline($this->container);
    }

    /**
     * Gifts helper getter
     *
     * @return Gifts
     */
    public function getGiftsObject() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new Gifts($this->container);
    }

    /**
     * Contacts helper getter
     *
     * @return Contacts
     */
    public function getContactsObject() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new Contacts($this->container);
    }

    /**
     * Messages helper getter
     *
     * @return Messages
     */
    public function getMessagesObject() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new Messages($this->container);
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
     * Variables object getter
     *
     * @return Variables
     */
    public function getVariablesObject() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new Variables($this->container);
    }

    /**
     * Account object getter
     *
     * @return Account
     */
    public function getAccountObject() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new Account($this->container);
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

        return self::$Instances[__FUNCTION__] = new Popularity($this->container);
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

        $Mamba = $this->getMamba();
        $Mamba->set('oid', $webUserId);

        $webUser = $Mamba->Anketa()->getInfo($webUserId);

//        $contactList = array();//$Mamba->Contacts()->getContactList();
//        $contactListIds = array();
//        if (isset($contactList['contacts'])) {
//            $contactListIds = array_map(function($item){return (int) $item['info']['oid'];}, $contactList['contacts']);
//        }

        $searchPreferencesObject = $this->getSearchPreferencesObject();

        $dataArray['webuser'] = array(
            'anketa'      => $webUser[0],
            'popularity'  => $this->getPopularityObject()->getInfo($this->getEnergyObject()->get($webUserId)),
            'battery'     => $this->getBatteryObject()->get($webUserId),
            'account'     => $this->getAccountObject()->get($webUserId),
            'preferences' => $searchPreferences,
            'stats'       => array(
                'mychoice'        => $this->getCountersObject()->get($webUserId, 'mychoice'),
                'visitors'        => $this->getCountersObject()->get($webUserId, 'visitors'),
                'visitors_unread' => $this->getCountersObject()->get($webUserId, 'visitors_unread'),
                'mutual'          => $this->getCountersObject()->get($webUserId, 'mutual'),
                'mutual_unread'   => $this->getCountersObject()->get($webUserId, 'mutual_unread'),
            ),
        );

        if ($photolineItems = $this->getPhotolineObject()->get($webUser[0]['location']['region_id'])) {
            $photoLinePhotos = $Mamba->Anketa()->getInfo($photolineIds = array_map(function($item) {
                return (int) $item['user_id'];
            }, $photolineItems), array('location'));

            $photoline = array();
            $n = 0;
            foreach ($photolineIds as $userId) {
                foreach ($photoLinePhotos as $photoLinePhotosItem) {
                    if ($photoLinePhotosItem['info']['oid'] == $userId) {
                        if ($photoLinePhotosItem['info']['square_photo_url']) {
                            $photoline[] = array(
                                'user_id'   => $userId,

                                'name'      => $photoLinePhotosItem['info']['name'],
                                'age'       => $photoLinePhotosItem['info']['age'],
                                'city'      => $photoLinePhotosItem['location']['city'],

                                'photo_url' => $photoLinePhotosItem['info']['square_photo_url'],
                                'comment'   => isset($photolineItems[$n]['comment']) ? htmlspecialchars($photolineItems[$n]['comment']) : null,
                            );
                        }

                        break;
                    }
                }

                $n++;
            }
        } else {
            $photoline = array();
        }

        $dataArray['photoline'] = $photoline;

        $dataArray['webuser']['json'] = json_encode($dataArray['webuser']);
        $dataArray['routes'] = json_encode($this->getRoutes());

        $dataArray['notification'] = array(
            'message' => $this->getNotificationsObject()->get($webUserId),
        );

        $dataArray['variables'] = array(
            'search_no_popular_block_hidden'      => $this->getVariablesObject()->get($webUserId, 'search_no_popular_block_hidden'),
            'notification_hidden'                 => $this->getVariablesObject()->get($webUserId, 'notification_hidden'),
        );

        $dataArray['gifts'] = \Mamba\EncountersBundle\Tools\Gifts\Gifts::getInstance()->toArray();

        if ($this->getRedis()->sCard($redisContactsKey = "contacts_by_{$webUserId}")) {
            $contacts = $this->getRedis()->sMembers($redisContactsKey);
            foreach ($contacts as $key => $userId) {
                $userId = (int) $userId;
                $contacts[$key] = $userId;

                if ($this->getVariablesObject()->get($userId, 'last_message_sent')) {
                    unset($contacts[$key]);
                }
            }

            $contacts = array_chunk($contacts, 100);
            $Mamba->multi();
            foreach ($contacts as $chunk) {
                $Mamba->Anketa()->getInfo($chunk, array());
            }

            if ($result = $Mamba->exec()) {
                $contacts = array();
                foreach ($result as $chunk) {
                    foreach ($chunk as $item) {
                        if ($item['info']['is_app_user'] == 0) {
                            $contacts[] = $item['info']['oid'];
                        }
                    }
                }

                if ($contacts) {
//                    shuffle($contacts);
//                    $contacts = array_chunk($contacts, 10);
//                    $contacts = array_shift($contacts);

                    $dataArray['non_app_users_contacts'] = $contacts;
                }
            }
        }

        $dataArray['controller'] = strtolower($this->getControllerName(get_called_class()));
        $dataArray['time'] = time();
        $dataArray['microtime'] = microtime(true);

        return $dataArray;
    }

    /**
     * Возвращает имя контроллера по имени класса
     *
     * @return str
     */
    protected function getControllerName($className) {
        $className = explode("\\", $className);
        $className = array_pop($className);
        return str_replace("Controller", "", $className);
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