<?php
namespace Mamba\EncountersBundle\Helpers;

use Mamba\EncountersBundle\EncountersBundle;

/**
 * Account
 *
 * @package EncountersBundle
 */
class Account extends Helper {

    const

        /**
         * Баланс по умолчанию
         *
         * @var int
         */
        DEFAULT_ACCOUNT = 10,

        /**
         * Минимальный баланс
         *
         * @var int
         */
        MINIMUM_ACCOUNT = 0,

        /**
         * Ключ для хранения баланса
         *
         * @var str
         */
        REDIS_USER_ACCOUNT_KEY = "account_by_%d"
    ;

    /**
     * Account getter
     *
     * @param int $userId
     * @return mixed
     */
    public function get($userId) {
        if (!is_int($userId)) {
            throw new AccountException("Invalid user id: \n" . var_export($userId, true));
        }

        $account = $this->getRedis()->get(sprintf(self::REDIS_USER_ACCOUNT_KEY, $userId));
        if (false === $account) {
            $this->set($userId, $account = self::DEFAULT_ACCOUNT);
        }

        return (int) $account;
    }

    /**
     * Account setter
     *
     * @param int $userId
     * @param int $account
     * @return mixed
     */
    public function set($userId, $account) {
        if (!is_int($userId)) {
            throw new AccountException("Invalid user id: \n" . var_export($userId, true));
        }

        if (is_int($account) && $account >= self::MINIMUM_ACCOUNT) {
            $result = $this->getRedis()->set(sprintf(self::REDIS_USER_ACCOUNT_KEY, $userId), $account);
            return $result;
        }

        throw new AccountException("Invalid account: \n" . var_export($account, true));
    }

    /**
     * Atomic increment
     *
     * @param int $userId
     * @param int $rate
     */
    public function incr($userId, $rate = 1) {
        if (!is_int($userId)) {
            throw new AccountException("Invalid user id: \n" . var_export($userId, true));
        }

        if (!is_int($rate)) {
            throw new AccountException("Invalid increment rate: \n" . var_export($rate, true));
        }

        $incrementResult = $this->getRedis()->incrBy(sprintf(self::REDIS_USER_ACCOUNT_KEY, $userId), $rate);
        if ($incrementResult < self::MINIMUM_ACCOUNT) {
            $result =  $this->set($userId, $incrementResult = self::MINIMUM_ACCOUNT);

            return $result;
        }

        return $incrementResult;
    }

    /**
     * Atomic decrement
     *
     * @param int $userId
     * @param int $rate
     */
    public function decr($userId, $rate = 1) {
        if (!is_int($rate)) {
            throw new AccountException("Invalid decrement rate: \n" . var_export($rate, true));
        }

        return $this->incr($userId, $rate * -1);
    }
}

/**
 * AccountException
 *
 * @package EncountersBundle
 */
class AccountException extends \Exception {

}