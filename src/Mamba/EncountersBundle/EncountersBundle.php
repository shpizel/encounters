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
         * Ключ для хранения приоритетной очереди
         *
         * @var str
         */
        REDIS_ZSET_USER_PRIORITY_QUEUE_KEY = "user_%d_priority_queue",

        /**
         * Ключ для хранения реверсной очереди
         *
         * @var str
         */
        REDIS_SET_USER_REVERSE_QUEUE_KEY = "user_%d_reverse_queue",

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
         * Имя функции обновления очереди
         *
         * @var str
         */
        GEARMAN_CURRENT_QUEUE_UPDATE_FUNCTION_NAME = 'updateCurrentQueue',

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
        REDIS_USER_LAST_SEARCH_QUEUE_UPDATED_KEY = 'user_%d_last_search_queue_updated',

        /**
         * Ключ для хранения инфы о кронах
         *
         * @var str
         */
        REDIS_HASH_USER_CRON_DETAILS_KEY = "user_%d_cron_details",

        /**
         * Хеш-ключ последнего обновления очереди поиска
         *
         * @var int
         */
        REDIS_HASH_KEY_SEARCH_QUEUE_UPDATED = 1,

        /**
         * Хеш-ключ последнего обновления очереди контактов
         *
         * @var int
         */
        REDIS_HASH_KEY_CONTACTS_QUEUE_UPDATED = 2,

        /**
         * Хеш-ключ последнего обновления очереди хитлиста
         *
         * @var int
         */
        REDIS_HASH_KEY_HITLIST_QUEUE_UPDATED = 3,

        /**
         * Хеш-ключ последнего обновления главной очереди
         *
         * @var int
         */
        REDIS_HASH_KEY_MAIN_QUEUE_UPDATED = 4,

        /**
         * Хеш-ключ последнего обновления текущей очереди
         *
         * @var int
         */
        REDIS_HASH_KEY_CURRENT_QUEUE_UPDATED = 5
    ;
}
