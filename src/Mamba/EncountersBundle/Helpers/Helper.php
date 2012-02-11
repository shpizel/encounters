<?php
namespace Mamba\EncountersBundle\Helpers;

use Mamba\RedisBundle\Redis;

/**
 * Helper
 *
 * @package EncountersBundle
 */
abstract class Helper {

    protected

        /**
         * Redis
         *
         * @var Redis
         */
        $Redis = null
    ;

    /**
     * Конструктор
     *
     * @return null
     */
    public function __construct(Redis $Redis) {
        $this->Redis = $Redis;
    }
}