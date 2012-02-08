<?php
namespace Mamba\EncountersBundle\Helpers\Queues;

use Mamba\RedisBundle\Redis;

/**
 * CurrentQueue
 *
 * @package EncountersBundle
 */
class CurrentQueue {

    const

        /**
         * Ключ для хранения текущей очереди
         *
         * @var str
         */
        REDIS_SET_USER_CURRENT_QUEUE_KEY = 'user_%d_current_queue'
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
     * Добавляет currentUser'a в текущую очередь webUser'а
     *
     * @param int $webUserId
     * @param int $currentUserId
     * @return mixed
     */
    public function put($webUserId, $currentUserId) {
        if (!is_int($webUserId)) {
            throw new CurrentQueueException("Invalid web user id: \n" . var_export($webUserId, true));
        }

        if (!is_int($currentUserId)) {
            throw new CurrentQueueException("Invalid curent user id: \n" . var_export($currentUserId, true));
        }

        return $this->Redis->sAdd($this->getRedisQueueKey($webUserId), $currentUserId);
    }

    /**
     * Дергает всю сет-очередь
     *
     * @param int $userId
     * @return mixed
     */
    public function getAll($userId) {
        if (!is_int($userId)) {
            throw new CurrentQueueException("Invalid user id: \n" . var_export($userId, true));
        }

        return $this->Redis->sMembers($this->getRedisQueueKey($userId));
    }

    /**
     * Удаляет currentUser'a из очереди webUser'а
     *
     * @param int $webUserId
     * @param int $currentUserId
     * @return mixed
     */
    public function remove($webUserId, $currentUserId) {
        if (!is_int($webUserId)) {
            throw new CurrentQueueException("Invalid web user id: \n" . var_export($webUserId, true));
        }

        if (!is_int($currentUserId)) {
            throw new CurrentQueueException("Invalid curent user id: \n" . var_export($currentUserId, true));
        }

        return $this->Redis->sRemove($this->getRedisQueueKey($webUserId), $currentUserId);
    }

    /**
     * Return popped element
     *
     * @param int $userId
     * @return mixed
     */
    public function pop($userId) {
        if (!is_int($userId)) {
            throw new CurrentQueueException("Invalid user id: \n" . var_export($userId, true));
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
            throw new CurrentQueueException("Invalid user id: \n" . var_export($userId, true));
        }

        return $this->Redis->sSize($this->getRedisQueueKey($userId));
    }

    /**
     * Возвращает ключ для хранения текущей очереди данного юзера
     *
     * @param int $userId
     * @return str
     */
    public function getRedisQueueKey($userId) {
        if (!is_int($userId)) {
            throw new CurrentQueueException("Invalid user id: \n" . var_export($userId));
        }

        return sprintf(self::REDIS_SET_USER_CURRENT_QUEUE_KEY, $userId);
    }

}

/**
 * CurrentQueueException
 *
 * @package EncountersBundle
 */
class CurrentQueueException extends \Exception {

}