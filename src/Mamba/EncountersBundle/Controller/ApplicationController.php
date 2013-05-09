<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\EncountersBundle;

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

use Symfony\Component\HttpFoundation\Response;

/**
 * ApplicationController
 *
 * @package EncountersBundle
 */
abstract class ApplicationController extends Controller {

    public static

        $metrics = array(
            'requests' => array(),
            'timeout'  => 0,
        )
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
    public function getBatteryHelper() {
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
    public function getPhotolineHelper() {
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
    public function getGiftsHelper() {
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
    public function getContactsHelper() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new Contacts($this->container);
    }

    /**
     * Leveldb getter
     *
     * @return Contacts
     */
    public function getLeveldb() {
        return $this->get('leveldb');
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

        return self::$Instances[__FUNCTION__] = new Messages($this->container);
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

        return self::$Instances[__FUNCTION__] = new Energy($this->container);
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

        return self::$Instances[__FUNCTION__] = new SearchPreferences($this->container);
    }

    /**
     * Platform settings getter
     *
     * @return PlatformSettings
     */
    public function getPlatformSettingsHelper() {
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
    public function getContactsQueueHelper() {
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
    public function getCurrentQueueHelper() {
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
    public function getHitlistQueueHelper() {
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
    public function getPriorityQueueHelper() {
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
    public function getSearchQueueHelper() {
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
    public function getViewedQueueHelper() {
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
    public function getCountersHelper() {
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
    public function getNotificationsHelper() {
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
    public function getServicesHelper() {
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
    public function getPurchasedHelper() {
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
    public function getStatsHelper() {
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
    public function getVariablesHelper() {
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
    public function getAccountHelper() {
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
    public function getPopularityHelper() {
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
            'platform' => json_encode($platformSettings = $this->getPlatformSettingsHelper()->get($webUserId = (int) $this->getMamba()->get('oid'))),
            'search'   => json_encode($searchPreferences = $this->getSearchPreferencesHelper()->get($webUserId))
        );

        $dataArray['platform'] = $platformSettings;

        $Mamba = $this->getMamba();
        $Mamba->set('oid', $webUserId);

        $webUser = $Mamba->Anketa()->getInfo($webUserId);

        $dataArray['webuser'] = array(
            'anketa'      => $webUser[0],
            'popularity'  => $this->getPopularityHelper()->getInfo($this->getEnergyHelper()->get($webUserId)),
            'battery'     => $this->getBatteryHelper()->get($webUserId),
            'account'     => $this->getAccountHelper()->get($webUserId),
            'preferences' => $searchPreferences,
        );

        if ($counters = $this->getCountersHelper()->getMulti([$webUserId], [/*'mychoice', */'visitors', 'visitors_unread', 'mutual', 'mutual_unread', 'messages_unread', 'events_unread'])) {
            $dataArray['webuser']['stats'] = $counters[$webUserId];
        }

        if ($photolineItems = $this->getPhotolineHelper()->get($webUser[0]['location']['region_id'])) {
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
            'message' => $this->getNotificationsHelper()->get($webUserId),
        );

        if ($variables = $this->getVariablesHelper()->getMulti([$webUserId], ['search_no_popular_block_hidden', 'notification_hidden'])) {
            $dataArray['variables'] = $variables[$webUserId];
        }

        $dataArray['gifts'] = \Mamba\EncountersBundle\Tools\Gifts\Gifts::getInstance()->toArray();

        if ($this->getRedis()->sCard($redisContactsKey = "contacts_by_{$webUserId}")) {
            $contacts = $this->getRedis()->sMembers($redisContactsKey);
            foreach ($contacts as $key => $userId) {
                $userId = (int) $userId;
                $contacts[$key] = $userId;

//                if ($this->getVariablesObject()->get($userId, 'last_message_sent')) {
//                    unset($contacts[$key]);
//                }
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
                    $dataArray['non_app_users_contacts'] = $contacts;
                }
            }
        }

        $dataArray['metrics'] = array(
            'mysql'   => self::$metrics,
            'redis'   => $this->getRedis()->getMetrics(),
            'leveldb' => $this->getLeveldb()->getMetrics(),
            'mamba'   => $this->getMamba()->getMetrics(),
        );

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

    private function updateLastAccess() {
        $Session = $this->getSession();
        if ($webUserId = (int) $Session->get(Mamba::SESSION_USER_ID_KEY)) {
            $this->getVariablesHelper()->set($webUserId, 'lastaccess', time());

            $this->getMemcache()->add("lastaccess_update_lock_by_user_" . $webUserId, time(), 750) &&
                $this->getGearman()->getClient()->doHighBackground(
                    EncountersBundle::GEARMAN_DATABASE_LASTACCESS_FUNCTION_NAME,
                    serialize(
                        array(
                            'user_id' => $webUserId,
                        )
                    )
                )
            ;
        }
    }

    /**
     * @param array $JSON
     * @return Response
     */
    public function JSONResponse(array $JSON) {
        $this->updateLastAccess();

        $JSON['metrics'] = array(
            'generation_time' => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],

            'mysql'   => self::$metrics,
            'redis'   => $this->getRedis()->getMetrics(),
            'leveldb' => $this->getLeveldb()->getMetrics(),
            'mamba'   => $this->getMamba()->getMetrics(),
        );

        return
            new Response(
                json_encode($JSON),
                200,
                array(
                    "content-type" => "application/json",
                )
            )
        ;
    }

    /**
     *
     * @param $view
     * @param array $parameters
     * @param Response $response
     * @return Response
     */
    public function TwigResponse($view, array $parameters = array(), Response $response = null) {
        $this->updateLastAccess();

        $parameters['generation_time'] = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
        $Response = $this->render($view, $parameters, $response);
        $Response->headers->set('P3P', 'CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
        return $Response;
    }
}