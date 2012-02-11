<?php
namespace Mamba\EncountersBundle\Helpers\Queues;

use Mamba\EncountersBundle\Helpers\Helper;

/**
 * SearchQueue
 *
 * @package EncountersBundle
 */
class SearchQueue extends Helper {

    const

        /**
         * Ключ для хранения очереди поиска
         *
         * @var str
         */
        REDIS_ZSET_USER_SEARCH_QUEUE_KEY = "user_%d_search_queue"
    ;

    /**
     * Добавляет элемент в очередь
     *
     * @param int $webUserId
     * @param int $currentUserId
     * @param $energy
     * @return mixed
     */
    public function put($webUserId, $currentUserId, $energy) {
        if (!is_int($webUserId)) {
            throw new SearchQueueException("Invalid web user id: \n" . var_export($webUserId, true));
        }

        if (!is_int($currentUserId)) {
            throw new SearchQueueException("Invalid curent user id: \n" . var_export($currentUserId, true));
        }

        return $this->Redis->zAdd($this->getRedisQueueKey($webUserId), $energy, $currentUserId);
    }

    /**
     * Возвращает размер очереди
     *
     * @param $userId
     * @return mixed
     */
    public function getSize($userId) {
        if (!is_int($userId)) {
            throw new SearchQueueException("Invalid user id: \n" . var_export($userId, true));
        }

        return $this->Redis->zSize($this->getRedisQueueKey($userId));
    }

    /**
     * Gets and returns queue range
     *
     * @param int $userId
     * @param int $from
     * @param int $to
     */
    public function getRange($userId, $from, $to) {
        if (!is_int($userId)) {
            throw new SearchQueueException("Invalid user id: \n" . var_export($userId, true));
        }

        if (!is_int($from)) {
            throw new SearchQueueException("Invalid from parameter: \n" . var_export($from, true));
        }

        if (!is_int($to)) {
            throw new SearchQueueException("Invalid to parameter: \n" . var_export($to, true));
        }

        return $this->Redis->zRange($this->getRedisQueueKey($userId), $from, $to);
    }

    /**
     * Удаляет currentUser'a из очереди поиска webUser'a
     *
     * @param int $webUserId
     * @param int $currentUserId
     */
    public function remove($webUserId, $currentUserId) {
        if (!is_int($webUserId)) {
            throw new SearchQueueException("Invalid web user id: \n" . var_export($webUserId, true));
        }

        if (!is_int($currentUserId)) {
            throw new SearchQueueException("Invalid curent user id: \n" . var_export($currentUserId, true));
        }

        $this->Redis->zDelete($this->getRedisQueueKey($webUserId), $currentUserId);
    }

    /**
     * Изменить энергию в очереди
     *
     * @param int $currentUserId
     * @param int $webUserId
     * @param $energy
     * @return mixed
     */
    public function changeEnergy($webUserId, $currentUserId, $energy) {
        if (!is_int($webUserId)) {
            throw new SearchQueueException("Invalid web user id: \n" . var_export($webUserId, true));
        }

        if (!is_int($currentUserId)) {
            throw new SearchQueueException("Invalid curent user id: \n" . var_export($currentUserId, true));
        }

        return $this->Redis->zIncrBy($zKey = $this->getRedisQueueKey($webUserId), $energy - $this->zScore($zKey, $currentUserId), $currentUserId);
    }

    /**
     * Возвращает ключ для хранения очереди поиска данного юзера
     *
     * @param int $userId
     * @return str
     */
    public function getRedisQueueKey($userId) {
        if (!is_int($userId)) {
            throw new SearchQueueException("Invalid user id: \n" . var_export($userId));
        }

        return sprintf(self::REDIS_ZSET_USER_SEARCH_QUEUE_KEY, $userId);
    }

}

/**
 * SearchQueueException
 *
 * @package EncountersBundle
 */
class SearchQueueException extends \Exception {

}