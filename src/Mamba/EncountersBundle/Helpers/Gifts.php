<?php
namespace Mamba\EncountersBundle\Helpers;

use Core\RedisBundle\Redis;
use Mamba\EncountersBundle\EncountersBundle;

/**
 * Gifts
 *
 * @package EncountersBundle
 */
class Gifts extends Helper {

    const

        /**
         * Gift url pattern
         *
         * @var str
         */
        GIFT_URL_PATTERN = "/bundles/encounters/images/gifts/{collection}/{filename}"
    ;

    private static

        /**
         * Доступные подарки
         *
         * @var array
         */
        $gifts = array(

            /**
             * Коллекция подарков
             *
             * @var array
             */
            'all' => array(

                'id' => 1,

                /**
                 * Включенность коллекции
                 *
                 * @var bool
                 */
                'enabled' => true,

                /**
                 * Сортировка
                 *
                 * @var int
                 */
                'order' => 1,

                /**
                 * Контент
                 *
                 * @var array
                 */
                'content' => array(
                    ['id' => 1, 'url' => '01.png', 'cost' => 3],
                    ['id' => 2, 'url' => '02.png', 'cost' => 3],
                    ['id' => 3, 'url' => '03.png', 'cost' => 1],
                    ['id' => 4, 'url' => '04.png', 'cost' => 1],
                    ['id' => 5, 'url' => '05.png', 'cost' => 3],
                    ['id' => 6, 'url' => '06.png', 'cost' => 3],
                    ['id' => 7, 'url' => '07.png', 'cost' => 1],
                    ['id' => 8, 'url' => '08.png', 'cost' => 1],
                ),
            )
        )
    ;

    /**
     * Gifts getter
     *
     * @return array
     */
    public function get() {
        if ($collections = $this->getCollections()) {
            $gifts = [];

            foreach ($collections as $collection) {
                $gifts[$collection] = $this->getGiftsFromCollection($collection);
            }

            return $gifts;
        }
    }

    /**
     * Return gifts collections
     *
     * @return array
     */
    public function getCollections() {
        $gifts = self::$gifts;

        uasort($gifts, function($a, $b) {
            $a = $a['order'];
            $b = $b['order'];

            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        });

        $gifts = array_reduce($gifts, function($item) {
            return $item['enabled'];
        });

        return $collections = keys($gifts);
    }

    /**
     * Return gifts from collection
     *
     * @param str $collection
     * @return array
     */
    public function getGiftsFromCollection($collection) {
        if (isset(self::$gifts[$collection])) {
            $gifts = self::$gifts[$collection]['content'];
            $gifts = array_reduce($gifts, function($item) {
                if (isset($item['enabled'])) {
                    return $item['enabled'];
                }

                return true;
            });

            return $gifts;
        }
    }
}

/**
 * GiftsException
 *
 * @package EncountersBundle
 */
class GiftsException extends \Exception {

}