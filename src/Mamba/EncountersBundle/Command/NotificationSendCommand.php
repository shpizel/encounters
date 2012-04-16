<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Command\CronScript;
use Mamba\EncountersBundle\EncountersBundle;
use Mamba\EncountersBundle\Helpers\SearchPreferences;
use Mamba\EncountersBundle\Helpers\Declensions;

/**
 * NotificationSendCommand
 *
 * @package EncountersBundle
 */
class NotificationSendCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Notification sender",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:notification:send"
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

            list($visitorsUnread, $mutualUnread) = array(
                (int) $this->getCountersObject()->get($userId, 'visitors_unread'),
                (int) $this->getCountersObject()->get($userId, 'mutual_unread'),
            );
        }
    }

    /**
     * Генерирует и возвращает сообщение от менеджера сообщений для карент юзера
     *
     * @param int $currentUserId
     * @return str
     */
    private function getNotifyMessage($currentUserId) {
        if ($anketa = $this->getMamba()->Anketa()->getInfo($currentUserId)) {
            $name = $anketa[0]['info']['name'];
            $name = explode(" ", $name);
            $name = array_shift($name);

            $visitorsUnread = (int) $this->getCountersObject()->get($currentUserId, 'visitors_unread');
            $mutualUnread   = (int) $this->getCountersObject()->get($currentUserId, 'mutual_unread');

            if ($visitorsUnread && !$mutualUnread) {
                if ($visitorsUnread == 1) {
                    $message = "$name, у вас появилась новая оценка в приложении «Выбиратор»!";
                } else {
                    $message = "$name, у вас $visitorsUnread новых оценок в приложении «Выбиратор»!";
                }
            } elseif ($mutualUnread && !$visitorsUnread) {
                $message = "$name, вам ответили взаимностью в приложении «Выбиратор»!";
            } elseif ($mutualUnread && $visitorsUnread) {
                $message = "$name, у вас $visitorsUnread новых оценок и $mutualUnread взаимных симпатии в приложении «Выбиратор»!";
            }

            if (isset($message)) {
                return $message;
            }
        }
    }
}