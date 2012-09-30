<?php
namespace Mamba\EncountersBundle\Helpers;

use Core\RedisBundle\Redis;

/**
 * Purchased
 *
 * @package EncountersBundle
 */
class Purchased extends Helper {

    const

        /**
         * Ключ для хранения данных об открытых ответах
         *
         * @var str
         */
        REDIS_SET_USER_PURCHASED_KEY = "purchased_by_%d"
    ;

    /**
     * Возвращает последнюю заказанную услугу (с удалением из списка)
     *
     * @param int $userId
     * @return mixed
     */
    public function exists($webUserId, $currentUserId) {
        if (!is_int($webUserId)) {
            throw new PurchasedException("Invalid web user id: \n" . var_export($webUserId, true));
        }

        if (!is_int($currentUserId)) {
            throw new PurchasedException("Invalid current user id: \n" . var_export($currentUserId, true));
        }

        return $this->getRedis()->sContains(sprintf(self::REDIS_SET_USER_PURCHASED_KEY, $webUserId), $currentUserId);
    }

    /**
     * Добавляет пользователя в список открытых
     *
     * @param int $webUserId
     * @param int $currentUserId
     */
    public function add($webUserId, $currentUserId) {
        if (!is_int($webUserId)) {
            throw new PurchasedException("Invalid web user id: \n" . var_export($webUserId, true));
        }

        if (!is_int($currentUserId)) {
            throw new PurchasedException("Invalid current user id: \n" . var_export($currentUserId, true));
        }

        return $this->getRedis()->sAdd(sprintf(self::REDIS_SET_USER_PURCHASED_KEY, $webUserId), $currentUserId);
    }
}

/**
 * PurchasedException
 *
 * @package EncountersBundle
 */
class PurchasedException extends \Exception {

}