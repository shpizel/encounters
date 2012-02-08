<?php
namespace Mamba\EncountersBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * EncountersBundle
 *
 * @package EncountersBundle
 */
class EncountersBundle extends Bundle {

    const

        /**
         * Имя функции обновления очереди хитлиста
         *
         * @var str
         */
        GEARMAN_HITLIST_QUEUE_UPDATE_FUNCTION_NAME = 'updateHitlistQueue',

        /**
         * Имя функции обновления очереди  контактов
         *
         * @var str
         */
        GEARMAN_CONTACTS_QUEUE_UPDATE_FUNCTION_NAME = 'updateContactsQueue',

        /**
         * Имя функции обновления очереди поиска
         *
         * @var str
         */
        GEARMAN_SEARCH_QUEUE_UPDATE_FUNCTION_NAME = 'updateSearchQueue',

        /**
         * Имя функции обновления очереди
         *
         * @var str
         */
        GEARMAN_CURRENT_QUEUE_UPDATE_FUNCTION_NAME = 'updateCurrentQueue',

        /**
         * Имя функции обновления памяти при изменении энергии
         *
         * @var str
         */
        GEARMAN_ = ''
    ;
}
