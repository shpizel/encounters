<?php
namespace Mamba\EncountersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Mamba\PlatformBundle\API\Mamba;
use Mamba\EncountersBundle\EncountersBundle;

/**
 * GameController
 *
 * @package EncountersBundle
 */
class GameController extends Controller {

    protected static

        /**
         * Баланс
         *
         * @var array
         */
        $balance = array(
            'search'   => 5 /** 7 */,
            'main'     => 1,
            'hitlist'  => 1,
            'contacts' => 1,
        )
    ;

    /**
     * Index action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {
        $Mamba = $this->get('Mamba');
        if ($platformSettings = $Mamba->getReady()) {
            var_dump($Mamba->Anketa()->getHitlist());
            exit();
            $Redis = $this->get('redis');

            /**
             * Наполним очередь согласно балансу
             *
             * @author shpizel
             */
            while (!$Redis->zSize(sprintf(EncountersBundle::REDIS_ZSET_USER_CURRENT_QUEUE_KEY, $Mamba->get('oid')))) {
                $searchQueueChunk   = $Redis->zRange(sprintf(EncountersBundle::REDIS_ZSET_USER_SEARCH_QUEUE_KEY, $Mamba->get('oid')), 0, self::$balance['search'] -1);
                $mainQueueChunk     = $Redis->zRange(sprintf(EncountersBundle::REDIS_ZSET_USER_MAIN_QUEUE_KEY, $Mamba->get('oid')), 0, self::$balance['main'] - 1);
                $hitlistQueueChunk  = $Redis->zRange(sprintf(EncountersBundle::REDIS_ZSET_USER_HITLIST_QUEUE_KEY, $Mamba->get('oid')), 0, self::$balance['search'] - 1);
                $contactsQueueChunk = $Redis->zRange(sprintf(EncountersBundle::REDIS_ZSET_USER_CONTACTS_QUEUE_KEY, $Mamba->get('oid')), 0, self::$balance['search'] - 1);

                foreach ($searchQueueChunk as $userId) {
                    $Redis->zDelete(sprintf(EncountersBundle::REDIS_ZSET_USER_SEARCH_QUEUE_KEY, $Mamba->get('oid')), $userId);
                }

                foreach ($mainQueueChunk as $userId) {
                    $Redis->zDelete(sprintf(EncountersBundle::REDIS_ZSET_USER_MAIN_QUEUE_KEY, $Mamba->get('oid')), $userId);
                }

                foreach ($hitlistQueueChunk as $userId) {
                    $Redis->zDelete(sprintf(EncountersBundle::REDIS_ZSET_USER_HITLIST_QUEUE_KEY, $Mamba->get('oid')), $userId);
                }

                foreach ($contactsQueueChunk as $userId) {
                    $Redis->zDelete(sprintf(EncountersBundle::REDIS_ZSET_USER_CONTACTS_QUEUE_KEY, $Mamba->get('oid')), $userId);
                }

                $currentQueueIds = array_merge($searchQueueChunk, $mainQueueChunk, $hitlistQueueChunk, $contactsQueueChunk);
                foreach ($currentQueueIds as $userId) {
                    if (!$Redis->hExists(sprintf(EncountersBundle::REDIS_HASH_USER_VIEWED_USERS_KEY, $Mamba->get('oid')), $userId)) {
                        $Redis->zAdd(sprintf(EncountersBundle::REDIS_ZSET_USER_CURRENT_QUEUE_KEY, $Mamba->get('oid')), 1, $userId);
                    }
                }
            }

            $currentQueueIds = $Redis->zRange(sprintf(EncountersBundle::REDIS_ZSET_USER_CURRENT_QUEUE_KEY, $Mamba->get('oid')), 0, array_sum(self::$balance) - 1);
            header("content-type:text/html; charset=utf8;");

            $data = $Mamba->Anketa()->getInfo($currentQueueIds);
            $Mamba->multi();
            foreach ($currentQueueIds as $userId) {
                $Mamba->Photos()->get($userId);
            }

            $currentUsersPhotos = $Mamba->exec();
            $currentUsersPhotos = array_map(function($item) {
                return $item['photos'];
            }, $currentUsersPhotos);

            foreach ($data as $k=>$info) {
                echo "<h1>" . $info['info']['name'] . "</h1>";

                echo "<div><button>ДА!</button><button>Возможно..</button><button>Нет</button></div><br>";

                $photos = $currentUsersPhotos[$k];
                echo "<img src=\"" . $photos[0]['huge_photo_url'] .  "\"><br><br>";

                foreach ($photos as $photo) {
                    echo "<img src=\"" . $photo['small_photo_url'] .  "\"> ";
                }
                echo "<hr/>";
            }

            exit();
        }

        return $this->redirect($this->generateUrl('welcome'));
    }
}