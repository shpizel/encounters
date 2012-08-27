<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Command\Script;
use Mamba\EncountersBundle\Helpers\SearchPreferences;

/**
 * AACommand
 *
 * @package EncountersBundle
 */
class AACommand extends Script {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "AA script",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "AA"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $Redis = $this->getRedis(1);

        $appUsers  = $Redis->hKeys(SearchPreferences::REDIS_HASH_USERS_SEARCH_PREFERENCES_KEY);
        foreach ($appUsers as $userId) {
            $userId = (int) $userId;
            $lastAccess = $this->getVariablesObject()->get($userId, 'lastaccess');
            if (!$lastAccess || ((time() - $lastAccess)/86400) > 28) {
                $Redis->del($this->getContactsQueueObject()->getRedisQueueKey($userId));
                $Redis->del($this->getCurrentQueueObject()->getRedisQueueKey($userId));
                $Redis->del($this->getHitlistQueueObject()->getRedisQueueKey($userId));
                $Redis->del($this->getSearchQueueObject()->getRedisQueueKey($userId));
            }
        }
    }
}