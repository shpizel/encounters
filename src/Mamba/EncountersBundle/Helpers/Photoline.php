<?php
namespace Mamba\EncountersBundle\Helpers;

use Core\RedisBundle\Redis;

/**
 * Photoline
 *
 * @package EncountersBundle
 */
class Photoline extends Helper {

    const

        /**
         * Ключ для хранения мордоленты
         *
         * @var str
         */
        REDIS_PHOTOLINE_KEY = "photoline"
    ;

    /**
     * Photoline getter
     *
     * @param int $limit = 25
     * @return mixed
     */
    public function get($limit = 25) {
        $items = $this->getRedis()->zRange(self::REDIS_PHOTOLINE_KEY, 0, $limit, true);

        $items = array_keys($items);
        foreach ($items as &$item) {
            $item = json_decode($item, true);
        }

        return $items;
    }

    /**
     * Photoline getter
     *
     * @param int $from
     * @param int $limit = 25
     * @return mixed
     */
    public function getbyRange($from, $limit = 25) {
        $items = $this->getRedis()->zRangeByScore(self::REDIS_PHOTOLINE_KEY, -1*$from, '-inf', array('withscores' => TRUE, 'limit' => array(0, $limit)));

        $items = array_keys($items);
        foreach ($items as &$item) {
            $item = json_decode($item, true);
        }

        return $items;
    }

    /**
     * Photoline adder
     *
     * @param int $userId
     * @param int $charge
     * @return mixed
     */
    public function add($userId) {
        if (!is_int($userId)) {
            throw new PhotolineException("Invalid user id: \n" . var_export($userId, true));
        }

        return $this->getRedis()->zAdd(self::REDIS_PHOTOLINE_KEY, -1*time(), json_encode(
            array(
                'user_id'   => $userId,
                'microtime' => microtime(true),
            )
        ));
    }
}

/**
 * PhotolineException
 *
 * @package EncountersBundle
 */
class PhotolineException extends \Exception {

}