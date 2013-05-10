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
         * Имя функции рассылки спама по контактам
         *
         * @var str
         */
        GEARMAN_CONTACTS_SEND_MESSAGE_FUNCTION_NAME = 'contacts:sendmessage',

        /**
         * Имя функции рассылки спама по контактам для мультигифта
         *
         * @var str
         */
        GEARMAN_CONTACTS_MULTI_GIFT_SEND_MESSAGE_FUNCTION_NAME = 'contacts:multigift:sendmessage',

        /**
         * Имя функции установки ачивок
         *
         * @var str
         */
        GEARMAN_ACHIEVEMENT_SET_FUNCTION_NAME = 'achievement:set',

        /**
         * Имя функции для обновления таблицы оценок
         *
         * @var str
         */
        GEARMAN_DATABASE_DECISIONS_UPDATE_FUNCTION_NAME = 'database:decisions:process',

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
        GEARMAN_DATABASE_USER_UPDATE_FUNCTION_NAME = 'database:user:update',

        /**
         * Имя функции для обновления онлайн-юзеров
         *
         * @var str
         */
        GEARMAN_DATABASE_LASTACCESS_FUNCTION_NAME = 'database:lastaccess:update',

        /**
         * Имя функции для обновления счетчиков непрочитанных сообщений
         *
         * @var str
         */
        GEARMAN_MESSENGER_UPDATE_COUNTERS_FUNCTION_NAME = 'database:messenger:counters:update',

        /**
         * Имя функции-обработчика взаимной симпатии (отправка сообщения)
         *
         * @var str
         */
        GEARMAN_MUTUAL_ICEBREAKER_FUNCTION_NAME = 'database:mutual:icebreaker'
    ;
}
