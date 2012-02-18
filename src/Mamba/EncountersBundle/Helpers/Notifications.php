<?php
namespace Mamba\EncountersBundle\Helpers;

use Mamba\RedisBundle\Redis;

/**
 * Notifications
 *
 * @package EncountersBundle
 */
class Notifications extends Helper {

    const

        /**
         * Ключ для хранения нотификаций
         *
         * @var str
         */
        REDIS_HASH_USER_NOTIFICATIONS_KEY = "notifications"
    ;

    /**
     * Notification getter
     *
     * @param int $userId
     * @return mixed
     */
    public function get($userId) {
        if (!is_int($userId)) {
            throw new NotificationsException("Invalid user id: \n" . var_export($userId, true));
        }

        return $this->Redis->hGet(self::REDIS_HASH_USER_NOTIFICATIONS_KEY, $userId);
    }

    /**
     * Notification setter
     *
     * @param int $userId
     * @param string $message
     */
    public function set($userId, $message) {
        if (!is_int($userId)) {
            throw new NotificationsException("Invalid user id: \n" . var_export($userId, true));
        }

        return $this->Redis->hSet(self::REDIS_HASH_USER_NOTIFICATIONS_KEY, $userId, $message);
    }

    /**
     * Notification remover
     *
     * @param int $userId
     */
    public function remove($userId) {
        if (!is_int($userId)) {
            throw new NotificationsException("Invalid user id: \n" . var_export($userId, true));
        }

        return $this->Redis->hDel(self::REDIS_HASH_USER_NOTIFICATIONS_KEY, $userId);
    }
}

/**
 * CountersException
 *
 * @package EncountersBundle
 */
class NotificationsException extends \Exception {

}