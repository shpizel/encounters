<?php
namespace Mamba\EncountersBundle\Helpers\Queues;

use Mamba\RedisBundle\Redis;

/**
 * ViewedQueue
 *
 * @package EncountersBundle
 */
class ViewedQueue {

    const

        /**
         * Ключ для хранения хеша проголосованных юзеров
         *
         * @var str
         */
        REDIS_HASH_USER_VIEWED_QUEUE_KEY = 'user_%d_viewed_queue'
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

        return $this->Redis->hSetNx($this->getRedisQueueKey($webUserId), $currentUserId, $data);
    }

    /**
     * Возвращает размер очереди
     *
     * @param $userId
     * @return mixed
     */
    public function getSize($userId) {
        if (!is_int($userId)) {
            throw new ViewedQueueException("Invalid user id: \n" . var_export($userId, true));
        }

        return $this->Redis->lSize($this->getRedisQueueKey($userId));
    }

    /**
     * Проверяет просмотрел ли currentUser webUser'ом
     *
     * @param int $webUserId
     * @param int $currentUserId
     */
    public function exists($webUserId, $currentUserId) {
        return $this->Redis->hExists($this->getRedisQueueKey($webUserId), $currentUserId);
    }

    /**
     * Возвращает ключ для хранения очереди просмотренных данным юзером
     *
     * @param int $userId
     * @return str
     */
    public function getRedisQueueKey($userId) {
        if (!is_int($userId)) {
            throw new ViewedQueueException("Invalid user id: \n" . var_export($userId));
        }

        return sprintf(self::REDIS_HASH_USER_VIEWED_QUEUE_KEY, $userId);
    }

}

/**
 * ViewedQueueException
 *
 * @package EncountersBundle
 */
class ViewedQueueException extends \Exception {

}