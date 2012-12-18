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
         * Ключ для хранения мордоленты по региону
         *
         * @var str
         */
        REDIS_PHOTOLINE_KEY = "photoline_%d"
    ;

    /**
     * Photoline getter
     *
     * @param int $regionId
     * @param int $limit = 25
     * @return mixed
     */
    public function get($regionId, $limit = 25) {
        $items = $this->getRedis()->zRange(sprintf(self::REDIS_PHOTOLINE_KEY, $regionId), 0, $limit, true);

        $items = array_keys($items);
        foreach ($items as &$item) {
            $item = json_decode($item, true);
        }

        return $items;
    }

    /**
     * Photoline getter
     *
     * @param int $regionId
     * @param int $from
     * @param int $limit = 25
     * @return mixed
     */
    public function getbyRange($regionId, $from, $limit = 25) {
        $items = $this->getRedis()->zRangeByScore(sprintf(self::REDIS_PHOTOLINE_KEY, $regionId), -1*$from, '-inf', array('withscores' => TRUE, 'limit' => array(0, $limit)));

        $items = array_keys($items);
        foreach ($items as &$item) {
            $item = json_decode($item, true);
        }

        return $items;
    }

    /**
     * Photoline adder
     *
     * @param int $regionId
     * @param int $userId
     * @return mixed
     */
    public function add($regionId, $userId) {
        if (!is_int($userId)) {
            throw new PhotolineException("Invalid user id: \n" . var_export($userId, true));
        }

        return $this->getRedis()->zAdd(sprintf(self::REDIS_PHOTOLINE_KEY, $regionId), -1*time(), json_encode(
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