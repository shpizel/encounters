<?php
namespace Mamba\EncountersBundle\Helpers\Queues;

use Mamba\EncountersBundle\Helpers\Helper;

/**
 * ViewedQueue
 *
 * @package EncountersBundle
 */
class ViewedQueue extends Helper {

    const

        /**
         * Ключ для хранения хеша проголосованных юзеров
         *
         * @var str
         */
        REDIS_HASH_USER_VIEWED_QUEUE_KEY = 'user_%d_viewed_queue'
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

        return $this->Redis->hSetNx($this->getRedisQueueKey($webUserId), $currentUserId, json_encode($data));
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
            throw new ViewedQueueException("Invalid curent user id: \n" . var_export($currentUserId, true));
        }

        if ($result = $this->Redis->hGet($this->getRedisQueueKey($webUserId), $currentUserId)) {
            return json_decode($result, true);
        }
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