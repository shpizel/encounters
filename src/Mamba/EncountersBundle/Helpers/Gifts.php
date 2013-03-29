<?php
namespace Mamba\EncountersBundle\Helpers;

/**
 * Gifts
 *
 * @package EncountersBundle
 */
class Gifts extends Helper {

    const

        /**
         * Ключ для хранения гифтов пользователей
         *
         * @var str
         */
        LEVELDB_USER_GIFT_KEY = "encounters:gift:%d"
    ;

    /**
     * Gifts getter
     *
     * @param int $userId
     * @return array|null
     */
    public function get($userId) {
        if (!is_int($userId)) {
            throw new GiftException("Invalid user_id type: ". gettype($userId));
        }

        $Leveldb = $this->getLeveldb();
        $Request = $Leveldb->get_range($userGiftsPrefix = sprintf(self::LEVELDB_USER_GIFT_KEY, $userId));
        $Leveldb->execute();

        if ($result = $Request->getResult()) {
            foreach ($result as $key=>$json) {
                if (strpos($key, $userGiftsPrefix) !== false) {
                    $result[$key] = json_decode($json, true);
                } else {
                    unset($result[$key]);
                }
            }

            return $result ? array_reverse($result) : null;
        }
    }

    /**
     * Gifts adder
     *
     * @param int $webUserId
     * @param int $currentUserId
     * @param int $giftId
     * @param string $comment
     * @return bool
     * @throws GiftsException
     */
    public function add($webUserId, $currentUserId, $giftId, $comment = '') {
        if (!is_int($webUserId)) {
            throw new GiftException("Invalid web user id type: ". gettype($webUserId));
        } elseif (!is_int($currentUserId)) {
            throw new GiftException("Invalid current user id type: ". gettype($currentUserId));
        } elseif (!is_int($giftId)) {
            throw new GiftException("Invalid gift id type: ". gettype($giftId));
        } elseif (!is_string($comment)) {
            throw new GiftException("Invalid comment type: ". gettype($comment));
        }

        $dataArray = array(
            'web_user_id' => $webUserId,
            'current_user_id' => $currentUserId,
            'gift_id' => $giftId,
            'comment' => $comment,
            'timestamp' => time(),
        );

        $giftKey = sprintf(self::LEVELDB_USER_GIFT_KEY, $currentUserId) . ":" . date("YmdHis");

        $Leveldb = $this->getLeveldb();
        $Request = $Leveldb->set(array(
            $giftKey => json_encode($dataArray),
        ));
        $Leveldb->execute();

        if ($Request->getResult() == 1) {
            $Stats = new Stats($this->Container);
            $Stats->incr('gifts-sent');

            return true;
        }

        return false;
    }
}

/**
 * GiftsException
 *
 * @package EncountersBundle
 */
class GiftsException extends \Exception {

}