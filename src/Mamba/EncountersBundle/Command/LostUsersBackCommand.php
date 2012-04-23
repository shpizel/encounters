<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Command\CronScript;
use Mamba\EncountersBundle\EncountersBundle;
use Mamba\EncountersBundle\Helpers\SearchPreferences;
use Mamba\EncountersBundle\Helpers\Declensions;

/**
 * LostUsersBackCommand
 *
 * @package EncountersBundle
 */
class LostUsersBackCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Lost users back",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:lostusers:back",

        /**
         * Интервал нотификаций
         *
         * @var int
         */
        NOTIFICATION_INTERVAL = 432000 /** 5 d */
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        list($Redis, $Memcache, $Mamba) = array(
            $this->getRedis(),
            $this->getMemcache(),
            $this->getMamba(),
        );

        foreach ($Redis->hKeys(SearchPreferences::REDIS_HASH_USERS_SEARCH_PREFERENCES_KEY) as $userId) {
            $userId = (int) $userId;

            $lastLostuserNotificationSent = $this->getVariablesObject()->get($userId, 'last_lostuser_notification_sent');
            if (!$lastLostuserNotificationSent || (time() - $lastLostuserNotificationSent > self::NOTIFICATION_INTERVAL)) {

            }
        }
    }
}