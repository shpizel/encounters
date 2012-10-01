<?php
namespace Mamba\EncountersBundle\Command;

use Core\ScriptBundle\CronScript;

use Mamba\EncountersBundle\Helpers\SearchPreferences;

/**
 * RedisCleanCommand
 *
 * @package EncountersBundle
 */
class RedisCleanCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Redis cleaner",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:redis:clean"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $Redis = $this->getRedis();

        $appUsers = $Redis->hKeys(SearchPreferences::REDIS_HASH_USERS_SEARCH_PREFERENCES_KEY);
        foreach ($appUsers as &$userId) {
            $userId = (int) $userId;
        }

        foreach ($appUsers as $userId) {
            $lastAccess = $this->getVariablesObject()->get($userId, 'lastaccess');
            if (!$lastAccess || ((time() - $lastAccess)/86400) > 28) {
                $Redis->del($this->getContactsQueueObject()->getRedisQueueKey($userId));
                $Redis->del($this->getCurrentQueueObject()->getRedisQueueKey($userId));
                $Redis->del($this->getHitlistQueueObject()->getRedisQueueKey($userId));
                $Redis->del($this->getSearchQueueObject()->getRedisQueueKey($userId));
            }
        }

        foreach ($appUsers as $userId) {
            $lastAccess = $this->getVariablesObject()->get($userId, 'lastaccess');
            if (!$lastAccess || ((time() - $lastAccess)/86400) > 6) {
                if ($purchased = $Redis->sMembers($purchasedKey = "purchased_by_{$userId}")) {
                    foreach ($purchased as $purchasedUserId) {
                        $purchasedUserId = (int) $purchasedUserId;

                        if (!$this->getViewedQueueObject()->get($purchasedUserId, $userId)) {
                            $Redis->sRemove($purchasedKey, $purchasedUserId);
                        }
                    }
                }
            }
        }
    }
}