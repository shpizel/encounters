<?php
namespace Mamba\EncountersBundle\Helpers\Queues;

use Mamba\RedisBundle\Redis;

/**
 * ContactsQueue
 *
 * @package EncountersBundle
 */
class ContactsQueue {

    const

        /**
         * Ключ для хранения очереди контактов
         *
         * @var str
         */
        REDIS_SET_USER_CONTACTS_QUEUE_KEY = "user_%d_contacts_queue"
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
    public function put($webUserId, $currentUserId) {
        if (!is_int($webUserId)) {
            throw new ContactsQueueException("Invalid web user id: \n" . var_export($webUserId, true));
        }

        if (!is_int($currentUserId)) {
            throw new ContactsQueueException("Invalid curent user id: \n" . var_export($currentUserId, true));
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
            throw new ContactsQueueException("Invalid user id: \n" . var_export($userId, true));
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
            throw new ContactsQueueException("Invalid user id: \n" . var_export($userId, true));
        }

        return $this->Redis->sSize($this->getRedisQueueKey($userId));
    }

    /**
     * Возвращает ключ для хранения очереди контактов данного юзера
     *
     * @param int $userId
     * @return str
     */
    public function getRedisQueueKey($userId) {
        if (!is_int($userId)) {
            throw new ContactsQueueException("Invalid user id: \n" . var_export($userId));
        }

        return sprintf(self::REDIS_SET_USER_CONTACTS_QUEUE_KEY, $userId);
    }
}

/**
 * ContactsQueueException
 *
 * @package EncountersBundle
 */
class ContactsQueueException extends \Exception {

}