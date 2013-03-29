<?php
namespace Mamba\EncountersBundle\Tools\Gifts;

use Mamba\EncountersBundle\Tools\Gifts\Collection;
use Mamba\EncountersBundle\Tools\Gifts\Gift;

/**
 * Gifts
 *
 * @package EncountersBundle
 */
class Gifts {

    private

        /**
         * Collections
         *
         * @var array
         */
        $Collections = array()
    ;

    private static

        /**
         * Instance
         *
         * @var Gifts
         */
        $Instance
    ;

    /**
     * Constructor
     */
    private function __construct() {
        $collectionId = $giftId = 1;

        $this->Collections[] = $Collection = $this->createCollection()
            ->setId($collectionId++)
            ->setEnabled(true)
            ->setName('Все')
            ->setOrder(1)
                ->addGift(
                    $this->createGift()
                        ->setId($order = $giftId++)
                        ->setOrder($order)
                        ->setEnabled(true)
                        ->setCost(3)
                        ->setUrl("http://meetwithme.ru/bundles/encounters/images/gifts/all/01.png")
                )
                ->addGift(
                    $this->createGift()
                        ->setId($order = $giftId++)
                        ->setOrder($order)
                        ->setEnabled(true)
                        ->setCost(3)
                        ->setUrl("http://meetwithme.ru/bundles/encounters/images/gifts/all/02.png")
                )
                ->addGift(
                    $this->createGift()
                        ->setId($order = $giftId++)
                        ->setOrder($order)
                        ->setEnabled(true)
                        ->setCost(1)
                        ->setUrl("http://meetwithme.ru/bundles/encounters/images/gifts/all/03.png")
                )
                ->addGift(
                    $this->createGift()
                        ->setId($order = $giftId++)
                        ->setOrder($order)
                        ->setEnabled(true)
                        ->setCost(1)
                        ->setUrl("http://meetwithme.ru/bundles/encounters/images/gifts/all/04.png")
                )
                ->addGift(
                    $this->createGift()
                        ->setId($order = $giftId++)
                        ->setOrder($order)
                        ->setEnabled(true)
                        ->setCost(3)
                        ->setUrl("http://meetwithme.ru/bundles/encounters/images/gifts/all/05.png")
                )
                ->addGift(
                    $this->createGift()
                        ->setId($order = $giftId++)
                        ->setOrder($order)
                        ->setEnabled(true)
                        ->setCost(3)
                        ->setUrl("http://meetwithme.ru/bundles/encounters/images/gifts/all/06.png")
                )
                ->addGift(
                    $this->createGift()
                        ->setId($order = $giftId++)
                        ->setOrder($order)
                        ->setEnabled(true)
                        ->setCost(1)
                        ->setUrl("http://meetwithme.ru/bundles/encounters/images/gifts/all/07.png")
                )
                ->addGift(
                    $this->createGift()
                        ->setId($order = $giftId++)
                        ->setOrder($order)
                        ->setEnabled(true)
                        ->setCost(1)
                        ->setUrl("http://meetwithme.ru/bundles/encounters/images/gifts/all/08.png")
                )
        ;
    }

    /**
     * @return Collection
     */
    private function createCollection() {
        return new Collection;
    }

    /**
     * @return Gift
     */
    private function createGift() {
        return new Gift;
    }

    /**
     * Collections getter
     *
     * @return array
     */
    public function getCollections() {
        return $this->Collections;
    }

    /**
     * Array getter
     *
     * @return array
     */
    public function toArray() {
        $Collections = $this->getCollections();

        $ret = array();

        foreach ($Collections as $Collection) {
            $ret[$Collection->getId()] = array(
                'id'      => $Collection->getId(),
                'name'    => $Collection->getName(),
                'enabled' => $Collection->getEnabled(),
                'order'   => $Collection->getOrder(),
                'gifts'   => array(),
            );

            foreach ($Collection->getGifts() as $Gift) {
                $ret[$Collection->getId()]['gifts'][$Gift->getId()] = array(
                    'id'      => $Gift->getId(),
                    'url'     => $Gift->getUrl(),
                    'enabled' => $Gift->getEnabled(),
                    'cost'    => $Gift->getCost(),
                    'order'   => $Gift->getOrder(),
                );
            }
        }

        return $ret;
    }

    /**
     * Gift getter
     *
     * @param int $giftId
     * @return array|null
     */
    public function getGiftById($giftId) {
        if (!is_int($giftId)) {
            throw new GiftsException("Invalid gift id type: ". gettype($giftId));
        }

        foreach ($this->getCollections() as $Collection) {
            foreach ($Collection->getGifts() as $Gift) {
                if ($Gift->getId() == $giftId) {
                    return $Gift;
                }
            }
        }
    }

    /**
     * Singleton getter
     *
     * @return Gifts
     */
    public static function getInstance() {
        return (!is_null(self::$Instance)) ? self::$Instance : self::$Instance = new self;
    }
}

/**
 * GiftsException
 *
 * @package EncountersBundle
 */
class GiftsException extends \Exception {

}