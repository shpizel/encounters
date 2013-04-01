<?php
namespace Mamba\EncountersBundle\Helpers;

use Mamba\EncountersBundle\EncountersBundle;

/**
 * Class Account
 *
 * @package Mamba\EncountersBundle\Helpers
 */
class Account extends Helper {

    const

        /**
         * Баланс по умолчанию
         *
         * @var int
         */
        DEFAULT_ACCOUNT = 0,

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
        LEVELDB_USER_ACCOUNT_KEY = "encounters:account:%d"
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

        $Leveldb = $this->getLeveldb();
        $Request = $Leveldb->get($leveldbKey = sprintf(self::LEVELDB_USER_ACCOUNT_KEY, $userId));
        $Leveldb->execute();

        $account = false;
        if (($result = $Request->getResult()) && isset($result[$leveldbKey])) {
            $account = $result[$leveldbKey];
        }

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

        $Leveldb = $this->getLeveldb();
        if (is_int($account) && $account >= self::MINIMUM_ACCOUNT) {
            $Request = $Leveldb->set(array(
                $leveldbKey = sprintf(self::LEVELDB_USER_ACCOUNT_KEY, $userId) => $account,
            ));
            $Leveldb->execute();

            if (true === $Request->getResult()) {
                return $account;
            } else {
                return false;
            }
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

        $Stats = new Stats($this->Container);
        if ($rate < 0) {
            $Stats->incr("account-decr", abs($rate));
        } else {
            $Stats->incr("account-incr", abs($rate));
        }

        $Leveldb = $this->getLeveldb();
        $Request = $Leveldb->inc_add(
            array(
                $leveldbKey = sprintf(self::LEVELDB_USER_ACCOUNT_KEY, $userId) => $rate,
            ),
            array(
                $leveldbKey => 0,
            )
        );
        $Leveldb->execute();

        $incrementResult = 0;
        if (($result = $Request->getResult()) && isset($result[$leveldbKey])) {
            $incrementResult = (int) $result[$leveldbKey];
        }

        if ($incrementResult < self::MINIMUM_ACCOUNT) {
            return $this->set($userId, $incrementResult = self::MINIMUM_ACCOUNT);
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
 * Class AccountException
 *
 * @package Mamba\EncountersBundle\Helpers
 */
class AccountException extends \Exception {

}
