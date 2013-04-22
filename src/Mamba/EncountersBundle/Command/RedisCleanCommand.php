<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\CronScript;

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

        $appUsers = $Redis->hKeys(SearchPreferences::REDIS_USER_SEARCH_PREFERENCES_KEY);
        foreach ($appUsers as &$userId) {
            $userId = (int) $userId;
        }

        foreach ($appUsers as $userId) {
            $lastAccess = $this->getVariablesHelper()->get($userId, 'lastaccess');
            if (!$lastAccess || ((time() - $lastAccess)/86400) > 28) {
                $Redis->del($this->getContactsQueueHelper()->getRedisQueueKey($userId));
                $Redis->del($this->getCurrentQueueHelper()->getRedisQueueKey($userId));
                $Redis->del($this->getHitlistQueueHelper()->getRedisQueueKey($userId));
                $Redis->del($this->getSearchQueueHelper()->getRedisQueueKey($userId));
            }
        }

        foreach ($appUsers as $userId) {
            $lastAccess = $this->getVariablesHelper()->get($userId, 'lastaccess');
            if (!$lastAccess || ((time() - $lastAccess)/86400) > 6) {
                if ($purchased = $Redis->sMembers($purchasedKey = "purchased_by_{$userId}")) {
                    foreach ($purchased as $purchasedUserId) {
                        $purchasedUserId = (int) $purchasedUserId;

                        if (!$this->getViewedQueueHelper()->get($purchasedUserId, $userId)) {
                            $Redis->sRemove($purchasedKey, $purchasedUserId);
                        }
                    }
                }
            }
        }
    }
}