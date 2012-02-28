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
        GEARMAN_HITLIST_QUEUE_UPDATE_FUNCTION_NAME = 'queue:hitlist:update',

        /**
         * Имя функции обновления очереди контактов
         *
         * @var str
         */
        GEARMAN_CONTACTS_QUEUE_UPDATE_FUNCTION_NAME = 'queue:contacts:update',

        /**
         * Имя функции обновления очереди поиска
         *
         * @var str
         */
        GEARMAN_SEARCH_QUEUE_UPDATE_FUNCTION_NAME = 'queue:search:update',

        /**
         * Имя функции обновления текущей очереди
         *
         * @var str
         */
        GEARMAN_CURRENT_QUEUE_UPDATE_FUNCTION_NAME = 'queue:current:update',

        /**
         * Имя функции рассылки нотификаций
         *
         * @var str
         */
        GEARMAN_NOTIFICATIONS_SEND_FUNCTION_NAME = 'notifications:send',

        /**
         * Имя функции для обновления таблицы оценок
         *
         * @var str
         */
        GEARMAN_DATABASE_DECISIONS_PROCESS_FUNCTION_NAME = 'database:decisions:process',

        /**
         * Имя функции для обновления таблицы энергий
         *
         * @var str
         */
        GEARMAN_DATABASE_ENERGY_UPDATE_FUNCTION_NAME = 'database:energy:update',

        /**
         * Имя функции для обновления таблицы пользователей
         *
         * @var str
         */
        GEARMAN_DATABASE_USER_UPDATE_FUNCTION_NAME = 'database:user:update'
    ;
}
