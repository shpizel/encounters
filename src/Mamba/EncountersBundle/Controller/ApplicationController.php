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
use Mamba\EncountersBundle\Helpers\Users;

use Core\MambaBundle\API\Mamba;
use Core\GearmanBundle\Gearman;
use Core\ServersBundle\Servers;
use Core\RedisBundle\Redis;
use Core\LeveldbBundle\Leveldb;
use Core\MySQLBundle\MySQL;
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
     * MySQL getter
     *
     * @return MySQL
     */
    public function getMySQL() {
        return $this->get('mysql');
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
     * Battery helper getter
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
     * Photoline helper getter
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
     * @return Leveldb
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
     * Energy helper getter
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
     * Search preferences helper getter
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
     * Platform settings helper getter
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
     * Contacts queue helper getter
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
     * Current queue helper getter
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
     * Hitlist queue helper getter
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
     * Priority queue helper getter
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
     * Search queue helper getter
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
     * Viewed queue helper getter
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
     * Counters helper getter
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
     * Notifications helper getter
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
     * Services helper getter
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
     * Purchased helper getter
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
     * Stats helper getter
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
     * Variables helper getter
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
     * Users helper getter
     *
     * @return Users
     */
    public function getUsersHelper() {
        if (isset(self::$Instances[__FUNCTION__])) {
            return self::$Instances[__FUNCTION__];
        }

        return self::$Instances[__FUNCTION__] = new Users($this->container);
    }

    /**
     * Account helper getter
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
     * Popularity helper getter
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
            'platform' => json_encode($platformSettings = $this->getSession()->get('platform_settings')),
            'search'   => json_encode($searchPreferences = $this->getSearchPreferencesHelper()->get($webUserId = $this->getMamba()->getWebUserId()))
        );

        $dataArray['platform'] = $platformSettings;

        $Mamba = $this->getMamba();
        $Mamba->set('oid', $webUserId);

        $webUser = $this->getUsersHelper()->getInfo($webUserId)[$webUserId];

        $dataArray['webuser'] = array(
            'anketa'      => $webUser,
            'popularity'  => $this->getPopularityHelper()->getInfo($this->getEnergyHelper()->get($webUserId)),
            'battery'     => $this->getBatteryHelper()->get($webUserId),
            'account'     => $this->getAccountHelper()->get($webUserId),
            'preferences' => $searchPreferences,
        );

        if ($counters = $this->getCountersHelper()->getMulti([$webUserId], ['mychoice', 'visitors', 'visitors_unread', 'mutual', 'mutual_unread', 'messages_unread', 'events_unread'])) {
            $dataArray['webuser']['stats'] = $counters[$webUserId];
        }

        if ($photolineItems = $this->getPhotolineHelper()->get($webUser['location']['region']['id'])) {
            $photoLinePhotos = $this->getUsersHelper()->getInfo(
                $photolineIds = array_map(function($item){return (int) $item['user_id'];}, $photolineItems),
                ['info', 'avatar', 'location']
            );

            $photoline = array();
            $n = 0;
            foreach ($photolineIds as $userId) {
                foreach ($photoLinePhotos as $photoLinePhotosItem) {
                    if ($photoLinePhotosItem['info']['user_id'] == $userId) {
                        if ($photoLinePhotosItem['avatar']['square_photo_url']) {
                            $photoline[] = array(
                                'user_id'   => $userId,

                                'name'      => $photoLinePhotosItem['info']['name'],
                                'age'       => $photoLinePhotosItem['info']['age'],
                                'city'      => $photoLinePhotosItem['location']['city']['name'],

                                'photo_url' => $photoLinePhotosItem['avatar']['square_photo_url'],
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

        if ($contacts = $this->getMemcache()->get("non_app_users_contacts_{$webUserId}")) {
            $dataArray['non_app_users_contacts'] = json_decode($contacts, true);
        } elseif ($this->getRedis()->sCard($redisContactsKey = "contacts_by_{$webUserId}")) {
            $contacts = $this->getRedis()->sMembers($redisContactsKey);
            foreach ($contacts as &$userId) {
                $userId = (int) $userId;
            }

            $contacts = array_chunk($contacts, 100);
            foreach ($contacts as $contactsChunkId=>$chunk) {
                $userInfo = $this->getUsersHelper()->getInfo($chunk, ['info']);

                foreach ($chunk as $chunkUserId) {
                    if (!(isset($userInfo[$chunkUserId]['info']['is_app_user']) && $userInfo[$chunkUserId]['info']['is_app_user'] == 1)) {
                        unset($contacts[$contactsChunkId][array_search($chunkUserId, $contacts[$contactsChunkId])]);
                    }
                }
            }

            $_contacts = $contacts;
            $contacts = [];
            foreach ($_contacts as $_contactsChunk) {
                $contacts = array_merge($chunk, $_contactsChunk);
            }

            if ($contacts) {
                $dataArray['non_app_users_contacts'] = $contacts;
                $this->getMemcache()->set("non_app_users_contacts_{$webUserId}", json_encode($contacts), 86400);
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

    private function updateLastAccess() {
        $Session = $this->getSession();
        if ($webUserId = (int) $Session->get(Mamba::SESSION_USER_ID_KEY)) {
            $this->getVariablesHelper()->set($webUserId, 'lastaccess', time());

            $this->getMemcache()->add("lastaccess_update_lock_by_user_" . $webUserId, time(), 60*15) &&
                $this->getGearman()->getClient()->doLowBackground(
                    EncountersBundle::GEARMAN_DATABASE_USERS_LASTACCESS_UPDATE_FUNCTION_NAME,
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
            'generation_time' => $generationTime = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"],

            'mysql'   => $this->getMySQL()->getMetrics(),
            'redis'   => $this->getRedis()->getMetrics(),
            'leveldb' => $this->getLeveldb()->getMetrics(),
            'mamba'   => $this->getMamba()->getMetrics(),
        );

        /**
         * Запишем данные по производительности в базу
         *
         * @author shpizel
         */
        $this->getGearman()->getClient()->doLowBackground(
            EncountersBundle::GEARMAN_DATABASE_PERFOMANCE_UPDATE_FUNCTION_NAME,
            serialize(
                array(
                    'route' => $this->getRequest()->get('_route'),
                    'generation_time' => ceil($generationTime*1000),

                    'mysql_requests_count' => count($JSON['metrics']['mysql']['requests']),
                    'mysql_timeout'        => ceil($JSON['metrics']['mysql']['timeout']*1000),
                    'mysql_requests'       => $JSON['metrics']['mysql']['requests'],

                    'redis_requests_count' => count($JSON['metrics']['redis']['requests']),
                    'redis_timeout'        => ceil($JSON['metrics']['redis']['timeout']*1000),
                    'redis_requests'       => $JSON['metrics']['redis']['requests'],

                    'leveldb_requests_count' => count($JSON['metrics']['leveldb']['requests']),
                    'leveldb_timeout'        => ceil($JSON['metrics']['leveldb']['timeout']*1000),
                    'leveldb_requests'       => $JSON['metrics']['leveldb']['requests'],

                    'mamba_requests_count' => count($JSON['metrics']['mamba']['requests']),
                    'mamba_timeout'        => ceil($JSON['metrics']['mamba']['timeout']*1000),
                    'mamba_requests'       => $JSON['metrics']['mamba']['requests'],

                    'time'  => time(),
                )
            )
        );

        return
            new Response(
                json_encode($JSON/*, JSON_PRETTY_PRINT*/),
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

        $parameters['metrics'] = array(
            'mysql'   => $this->getMySQL()->getMetrics(),
            'redis'   => $this->getRedis()->getMetrics(),
            'leveldb' => $this->getLeveldb()->getMetrics(),
            'mamba'   => $this->getMamba()->getMetrics(),
        );

        $generationTime = $parameters['generation_time'] = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];

        /**
         * Запишем данные по производительности в базу
         *
         * @author shpizel
         */
        isset($parameters['metrics']) && $this->getGearman()->getClient()->doLowBackground(
            EncountersBundle::GEARMAN_DATABASE_PERFOMANCE_UPDATE_FUNCTION_NAME,
            serialize(
                array(
                    'route' => $this->getRequest()->get('_route'),
                    'generation_time' => ceil($generationTime*1000),

                    'mysql_requests_count' => count($parameters['metrics']['mysql']['requests']),
                    'mysql_timeout'        => ceil($parameters['metrics']['mysql']['timeout']*1000),
                    'mysql_requests'       => $parameters['metrics']['mysql']['requests'],

                    'redis_requests_count' => count($parameters['metrics']['redis']['requests']),
                    'redis_timeout'        => ceil($parameters['metrics']['redis']['timeout']*1000),
                    'redis_requests'       => $parameters['metrics']['redis']['requests'],

                    'leveldb_requests_count' => count($parameters['metrics']['leveldb']['requests']),
                    'leveldb_timeout'        => ceil($parameters['metrics']['leveldb']['timeout']*1000),
                    'leveldb_requests'       => $parameters['metrics']['leveldb']['requests'],

                    'mamba_requests_count' => count($parameters['metrics']['mamba']['requests']),
                    'mamba_timeout'        => ceil($parameters['metrics']['mamba']['timeout']*1000),
                    'mamba_requests'       => $parameters['metrics']['mamba']['requests'],

                    'time'  => time(),
                )
            )
        );

        $Response = $this->render($view, $parameters, $response);
        $Response->headers->set('P3P', 'CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');

        return $Response;
    }
}