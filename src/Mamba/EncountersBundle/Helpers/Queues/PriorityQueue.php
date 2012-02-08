<?php
namespace Mamba\EncountersBundle\Helpers\Queues;

use Mamba\RedisBundle\Redis;

/**
 * PriorityQueue
 *
 * @package EncountersBundle
 */
class PriorityQueue {

    const

        /**
         * Ключ для хранения приоритетной очереди
         *
         * @var str
         */
        REDIS_SET_USER_PRIORITY_QUEUE_KEY = "user_%d_priority_queue"
    ;

    private

        /**
         * Redis
         *
         * @var \Mamba\RedisBundle\Redis $Redis
         */
        $Redis = null
    ;

    /**
     * Конструктор
     *
     * @param \Mamba\RedisBundle\Redis $Redis
     */
    public function __construct(Redis $Redis) {
        $this->Redis = $Redis;
    }

    /**
     * Добавляет currentUser'a в очередь просмотренных webUser'ом
     *
     * @param int $webUserId
     * @param int $currentUserId
     * @return mixed
     */
    public function put($webUserId, $currentUserId, $energy) {
        if (!is_int($webUserId)) {
            throw new PriorityQueueException("Invalid web user id: \n" . var_export($webUserId, true));
        }

        if (!is_int($currentUserId)) {
            throw new PriorityQueueException("Invalid curent user id: \n" . var_export($currentUserId, true));
        }

        return $this->Redis->sAdd($this->getRedisQueueKey($webUserId), $currentUserId);
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

        return $this->Redis->sPop($this->getRedisQueueKey($userId));
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

        return $this->Redis->sSize($this->getRedisQueueKey($userId));
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