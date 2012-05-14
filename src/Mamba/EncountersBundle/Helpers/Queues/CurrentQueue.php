<?php
namespace Mamba\EncountersBundle\Helpers\Queues;

use Mamba\EncountersBundle\Helpers\Helper;

/**
 * CurrentQueue
 *
 * @package EncountersBundle
 */
class CurrentQueue extends Helper {

    const

        /**
         * Ключ для хранения текущей очереди
         *
         * @var str
         */
        REDIS_SET_USER_CURRENT_QUEUE_KEY = 'user_%d_current_queue'
    ;

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
            throw new CurrentQueueException("Invalid current user id: \n" . var_export($currentUserId, true));
        }

        return $this->getRedis()->sAdd($this->getRedisQueueKey($webUserId), $currentUserId);
    }

    /**
     * Проверяет наличие current'a в текущей очереди webuser'a
     *
     * @param int $webUserId
     * @param int $currentUserId
     * @return bool
     */
    public function exists($webUserId, $currentUserId) {
        if (!is_int($webUserId)) {
            throw new CurrentQueueException("Invalid web user id: \n" . var_export($webUserId, true));
        }

        if (!is_int($currentUserId)) {
            throw new CurrentQueueException("Invalid current user id: \n" . var_export($currentUserId, true));
        }

        return $this->getRedis()->sContains($this->getRedisQueueKey($webUserId), $currentUserId);
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

        return $this->getRedis()->sMembers($this->getRedisQueueKey($userId));
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
            throw new CurrentQueueException("Invalid current user id: \n" . var_export($currentUserId, true));
        }

        return $this->getRedis()->sRemove($this->getRedisQueueKey($webUserId), $currentUserId);
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
            throw new CurrentQueueException("Invalid user id: \n" . var_export($userId, true));
        }

        return $this->getRedis()->sSize($this->getRedisQueueKey($userId));
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