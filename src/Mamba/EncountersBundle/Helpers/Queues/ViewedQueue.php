<?php
namespace Mamba\EncountersBundle\Helpers\Queues;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Mamba\EncountersBundle\Helpers\Helper;
use PDO;

/**
 * ViewedQueue
 *
 * @package EncountersBundle
 */
class ViewedQueue extends Helper {

    const

        /**
         * Ключ для хранения итема голосования
         *
         * @var str
         */
        MEMCACHE_VIEWED_QUEUE_ITEM_KEY = 'decision_from_%d_to_%d',

        /**
         * Время жизни одной оценки в кеше
         *
         * @var int
         */
        DECISION_LIFETIME = 1209600 /** 2 недели */
    ;

    /**
     * Добавляет currentUser'a в очередь просмотренных webUser'ом
     *
     * @param int $webUserId
     * @param int $currentUserId
     * @param mixed $data
     * @return mixed
     */
    public function put($webUserId, $currentUserId, $data) {
        if (!is_int($webUserId)) {
            throw new ViewedQueueException("Invalid web user id: \n" . var_export($webUserId, true));
        }

        if (!is_int($currentUserId)) {
            throw new ViewedQueueException("Invalid curent user id: \n" . var_export($currentUserId, true));
        }

        return $this->getMemcache()->set(sprintf(self::MEMCACHE_VIEWED_QUEUE_ITEM_KEY, $webUserId, $currentUserId), json_encode($data), self::DECISION_LIFETIME);
    }

    /**
     * Getter
     *
     * @param $webUserId
     * @param $currentUserId
     * @return mixed
     * @throws ViewedQueueException
     */
    public function get($webUserId, $currentUserId) {
        if (!is_int($webUserId)) {
            throw new ViewedQueueException("Invalid web user id: \n" . var_export($webUserId, true));
        }

        if (!is_int($currentUserId)) {
            throw new ViewedQueueException("Invalid current user id: \n" . var_export($currentUserId, true));
        }

        if ($result = $this->getMemcache()->get(sprintf(self::MEMCACHE_VIEWED_QUEUE_ITEM_KEY, $webUserId, $currentUserId))) {
            if ($data = json_decode($result, true)) {
                return $data;
            } else {
                return;
            }
        }

        $startTime = microtime(true);
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql = "SELECT * FROM Decisions where web_user_id = $webUserId and current_user_id = $currentUserId LIMIT 1");

        if ($stmt->execute()) {

            \Mamba\EncountersBundle\Controller\ApplicationController::$metrics['requests'][] = array(
                'method'  => $sql,
                'args'  => null,
                'timeout' => $timeout = microtime(true) - $startTime,
            );

            \Mamba\EncountersBundle\Controller\ApplicationController::$metrics['timeout']+=$timeout;

            if ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->put($webUserId, $currentUserId, $data = array('ts' => (int) $item['changed'], 'decision' => (int) $item['decision']));
                return $data;
            } else {
                $this->put($webUserId, $currentUserId, array());
            }
        }
    }

    /**
     * Проверяет просмотрел ли currentUser webUser'ом
     *
     * @param int $webUserId
     * @param int $currentUserId
     */
    public function exists($webUserId, $currentUserId) {
        return (bool) $this->get($webUserId, $currentUserId);
    }
}

/**
 * ViewedQueueException
 *
 * @package EncountersBundle
 */
class ViewedQueueException extends \Exception {

}