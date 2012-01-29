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
         * Ключ для хранения хеша пользовательских настроек поиска
         *
         * @var str
         */
        REDIS_HASH_USER_SEARCH_PREFERENCES_KEY = "user_%d_search_preferences",

        /**
         * Ключ для хранения очереди хитлиста
         *
         * @var str
         */
        REDIS_ZSET_USER_HITLIST_QUEUE_KEY = "user_%d_hitlist_queue",

        /**
         * Ключ для хранения очереди контактов
         *
         * @var str
         */
        REDIS_ZSET_USER_CONTACTS_QUEUE_KEY = "user_%d_contacts_queue",

        /**
         * Ключ для хранения очереди поиска
         *
         * @var str
         */
        REDIS_ZSET_USER_SEARCH_QUEUE_KEY = "user_%d_search_queue",

        /**
         * Ключ для хранения главной очереди
         *
         * @var str
         */
        REDIS_ZSET_USER_MAIN_QUEUE_KEY = "user_%d_main_queue",

        /**
         * Ключ для хранения текущей очереди
         *
         * @var str
         */
        REDIS_ZSET_USER_CURRENT_QUEUE_KEY = 'user_%d_current_queue',

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
         * Ключ для хранения хеша проголосованных юзеров
         *
         * @var str
         */
        REDIS_HASH_USER_VIEWED_USERS_KEY = 'user_%d_viewed_users',

        /**
         * Ключ для храненения времени последнего обновления очереди хитлиста
         *
         * @var str
         */
        REDIS_USER_LAST_HITLIST_QUEUE_UPDATED_KEY = 'user_%d_last_hitlist_queue_updated',

        /**
         * Ключ для храненения времени последнего обновления очереди контактов
         *
         * @var str
         */
        REDIS_USER_LAST_CONTACTS_QUEUE_UPDATED_KEY = 'user_%d_last_contacts_queue_updated',

        /**
         * Ключ для храненения времени последнего обновления очереди поиска
         *
         * @var str
         */
        REDIS_USER_LAST_SEARCH_QUEUE_UPDATED_KEY = 'user_%d_last_search_queue_updated'
    ;
}
