<?php
namespace Mamba\EncountersBundle\Helpers\Queues;

use Mamba\RedisBundle\Redis;

/**
 * ReverseQueue
 *
 * @package EncountersBundle
 */
class ReverseQueue {

    const

        /**
         * Ключ для хранения обратной очереди
         *
         * @var str
         */
        REDIS_SET_USER_REVERSE_QUEUE_KEY = "user_%d_reverse_queue"
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
     * Добавляет currentUser'a в обратную очередь webUser'а
     *
     * @param int $webUserId
     * @param int $currentUserId
     * @return mixed
     */
    public function put($webUserId, $currentUserId) {
        if (!is_int($webUserId)) {
            throw new ReverseQueueException("Invalid web user id: \n" . var_export($webUserId, true));
        }

        if (!is_int($currentUserId)) {
            throw new ReverseQueueException("Invalid curent user id: \n" . var_export($currentUserId, true));
        }

        return $this->Redis->sAdd($this->getRedisQueueKey($currentUserId), $webUserId);
    }

    /**
     * Возвращает размер очереди
     *
     * @param $userId
     * @return mixed
     */
    public function getSize($userId) {
        if (!is_int($userId)) {
            throw new ReverseQueueException("Invalid user id: \n" . var_export($userId, true));
        }

        return $this->Redis->sSize($this->getRedisQueueKey($userId));
    }

    /**
     * Удаляет currentUser'a из обратной очереди webUser'а
     *
     * @param int $webUserId
     * @param int $currentUserId
     * @return mixed
     */
    public function remove($webUserId, $currentUserId) {
        if (!is_int($webUserId)) {
            throw new ReverseQueueException("Invalid web user id: \n" . var_export($webUserId, true));
        }

        if (!is_int($currentUserId)) {
            throw new ReverseQueueException("Invalid curent user id: \n" . var_export($currentUserId, true));
        }

        return $this->Redis->sRemove($this->getRedisQueueKey($webUserId), $currentUserId);
    }

    /**
     * Возвращает ключ для хранения очереди хитлиста данного юзера
     *
     * @param int $userId
     * @return str
     */
    public function getRedisQueueKey($userId) {
        if (!is_int($userId)) {
            throw new ReverseQueueException("Invalid user id: \n" . var_export($userId));
        }

        return sprintf(self::REDIS_SET_USER_REVERSE_QUEUE_KEY, $userId);
    }
}

/**
 * ReverseQueueException
 *
 * @package EncountersBundle
 */
class ReverseQueueException extends \Exception {

}