<?php
namespace Mamba\EncountersBundle\Helpers\Gifts;

use Mamba\EncountersBundle\Helpers\Gifts\Gift;

/**
 * Class Collection
 *
 * @package Mamba\EncountersBundle\Helpers
 */
class Collection {

    private

        /**
         * Collection id
         *
         * @var int
         */
        $id,

        /**
         * Collection name
         *
         * @var string
         */
        $name = null,

        /**
         * Collection status (enabled || disabled)
         *
         * @var bool
         */
        $enabled = false,

        /**
         * Order
         *
         * @var int
         */
        $order = 0,

        /**
         * Gifts in collection
         *
         * @var array
         */
        $gifts = array()
    ;

    /**
     * Collection id getter
     *
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        if (!is_int($id)) {
            throw new CollectionException("Invalid id type: ". gettype($id));
        }

        $this->id = $id;
        return $this;
    }

    /**
     * Name getter
     *
     * @return string|null
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Name setter
     *
     * @param string $name
     * @return $this
     * @throws CollectionException
     */
    public function setName($name) {
        if (!is_string($name)) {
            throw new CollectionException("Invalid name type: ". gettype($name));
        }

        $this->name = $name;
        return $this;
    }

    /**
     * Enabled getter
     *
     * @return bool
     */
    public function getEnabled() {
        return $this->enabled;
    }

    /**
     * Enabled setter
     *
     * @param bool $enabled
     * @return $this
     * @throws CollectionException
     */
    public function setEnabled($enabled) {
        if (!is_bool($enabled)) {
            throw new CollectionException("Invalid enabled type: ". gettype($enabled));
        }

        $this->enabled = $enabled;
        return $this;
    }

    /**
     * Order getter
     *
     * @return int
     */
    public function getOrder() {
        return $this->order;
    }

    /**
     * Order setter
     *
     * @param int $order
     * @return $this
     * @throws GiftException
     */
    public function setOrder($order) {
        if (!is_int($order)) {
            throw new CollectionException("Invalid order type: " . gettype($order));
        }

        $this->order = $order;
        return $this;
    }

    /**
     * Adds gift to a collection
     *
     * @param Gift $Gift
     * @return $this
     */
    public function addGift(Gift $Gift) {
        $this->gifts[] = $Gift;

        uasort($this->gifts, function($a, $b) {
            $a = $a->getOrder();
            $b = $b->getOrder();

            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        });

        return $this;
    }

    /**
     * Gifts getter
     *
     * @param bool $onlyEnabled true
     * @return array
     */
    public function getGifts($onlyEnabled = true) {
        if ($onlyEnabled) {
            $gifts = array_filter($this->gifts, function($item) {
                return $item->getEnabled();
            });
        }

        return $gifts;
    }
}

/**
 * Class CollectionException
 *
 * @package Mamba\EncountersBundle\Helpers
 */
class CollectionException extends \Exception {

}