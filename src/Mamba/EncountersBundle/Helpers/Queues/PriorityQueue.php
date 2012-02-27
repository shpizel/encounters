<?php
namespace Mamba\EncountersBundle\Helpers\Queues;

use Mamba\EncountersBundle\Helpers\Helper;

/**
 * PriorityQueue
 *
 * @package EncountersBundle
 */
class PriorityQueue extends Helper {

    const

        /**
         * Ключ для хранения приоритетной очереди
         *
         * @var str
         */
        REDIS_SET_USER_PRIORITY_QUEUE_KEY = "user_%d_priority_queue"
    ;

    /**
     * Добавляет currentUser'a в очередь просмотренных webUser'ом
     *
     * @param int $webUserId
     * @param int $currentUserId
     * @return mixed
     */
    public function put($webUserId, $currentUserId) {
        if (!is_int($webUserId)) {
            throw new PriorityQueueException("Invalid web user id: \n" . var_export($webUserId, true));
        }

        if (!is_int($currentUserId)) {
            throw new PriorityQueueException("Invalid curent user id: \n" . var_export($currentUserId, true));
        }

        return $this->getRedis()->sAdd($this->getRedisQueueKey($webUserId), $currentUserId);
    }

    /**
     * Return popped element
     *
     * @param int $userId
     * @return mixed
     */
    public function pop($userId) {
        if (!is_int($userId)) {
            throw new PriorityQueueException("Invalid user id: \n" . var_export($userId, true));
        }

        return $this->getRedis()->sPop($this->getRedisQueueKey($userId));
    }

    /**
     * Возвращает размер очереди
     *
     * @param $userId
     * @return mixed
     */
    public function getSize($userId) {
        if (!is_int($userId)) {
            throw new PriorityQueueException("Invalid user id: \n" . var_export($userId, true));
        }

        return $this->getRedis()->sSize($this->getRedisQueueKey($userId));
    }

    /**
     * Возвращает ключ для хранения очереди хитлиста данного юзера
     *
     * @param int $userId
     * @return str
     */
    public function getRedisQueueKey($userId) {
        if (!is_int($userId)) {
            throw new PriorityQueueException("Invalid user id: \n" . var_export($userId));
        }

        return sprintf(self::REDIS_SET_USER_PRIORITY_QUEUE_KEY, $userId);
    }
}

/**
 * PriorityQueueException
 *
 * @package EncountersBundle
 */
class PriorityQueueException extends \Exception {

}