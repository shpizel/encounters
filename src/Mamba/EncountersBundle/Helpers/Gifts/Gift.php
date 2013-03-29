<?php
namespace Mamba\EncountersBundle\Helpers\Gifts;

/**
 * Class Gift
 *
 * @package Mamba\EncountersBundle\Helpers
 */
class Gift {

    private

        /**
         * Gift id
         *
         * @var int
         */
        $id,

        /**
         * Gift image url
         *
         * @var string
         */
        $url = null,

        /**
         * Gift status (enabled || disabled)
         *
         * @var bool
         */
        $enabled = false,

        /**
         * Gift cost
         *
         * @var int
         */
        $cost,

        /**
         * Order
         *
         * @var int
         */
        $order = 0
    ;

    /**
     * Gift id getter
     *
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Gift id setter
     *
     * @param int $id
     * @return $this
     * @throws GiftException
     */
    public function setId($id) {
        if (!is_int($id)) {
            throw new GiftException("Invalid id type: ". gettype($id));
        }

        $this->id = $id;
        return $this;
    }

    /**
     * Gift url getter
     *
     * @return string
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * Url setter
     *
     * @param string $url
     * @return $this
     * @throws GiftException
     */
    public function setUrl($url) {
        if (!is_string($url)) {
            throw new GiftException("Invalid url type: ". gettype($url));
        }

        $this->url = $url;
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
     * @throws GiftException
     */
    public function setEnabled($enabled) {
        if (!is_bool($enabled)) {
            throw new GiftException("Invalid enabled type: " . gettype($enabled));
        }

        $this->enabled = $enabled;
        return $this;
    }

    /**
     * Cost getter
     *
     * @return int
     */
    public function getCost() {
        return $this->cost;
    }

    /**
     * Cost setter
     *
     * @param int $cost
     * @return $this
     * @throws GiftException
     */
    public function setCost($cost) {
        if (!is_int($cost)) {
            throw new GiftException("Invalid cost type: " . gettype($cost));
        }

        $this->cost = $cost;
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
            throw new GiftException("Invalid order type: " . gettype($order));
        }

        $this->order = $order;
        return $this;
    }
}

/**
 * Class GiftException
 *
 * @package Mamba\EncountersBundle\Helpers
 */
class GiftException extends \Exception {

}