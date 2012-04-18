<?php
namespace Mamba\EncountersBundle\Helpers;

use Mamba\RedisBundle\Redis;
use Mamba\EncountersBundle\Helpers\Variables;

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
        REDIS_HASH_USER_NOTIFICATIONS_KEY = "notifications_by_%d"
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

        if ($result = $this->getRedis()->lGet(sprintf(self::REDIS_HASH_USER_NOTIFICATIONS_KEY, $userId), -1)) {
            $result = json_decode($result, true);
            return $result['data'];
        }
    }

    /**
     * All notifications getter
     *
     * @param int $userId
     * @return array
     */
    public function getAll($userId) {
        if (!is_int($userId)) {
            throw new NotificationsException("Invalid user id: \n" . var_export($userId, true));
        }

        return $this->getRedis()->lRange(sprintf(self::REDIS_HASH_USER_NOTIFICATIONS_KEY, $userId), 0, -1);
    }

    /**
     * Notification adder
     *
     * @param int $userId
     * @param string $message
     */
    public function add($userId, $message) {
        if (!is_int($userId)) {
            throw new NotificationsException("Invalid user id: \n" . var_export($userId, true));
        }

        $Variables = new Variables($this->Container);
        $Variables->set($userId, 'notification_hidden', 0);

        return
            $this->getRedis()->rPush(sprintf(self::REDIS_HASH_USER_NOTIFICATIONS_KEY, $userId),
                json_encode(
                    array(
                        'data' => $message,
                        'ts'   => time(),
                    )
                )
            )
        ;
    }
}

/**
 * CountersException
 *
 * @package EncountersBundle
 */
class NotificationsException extends \Exception {

}