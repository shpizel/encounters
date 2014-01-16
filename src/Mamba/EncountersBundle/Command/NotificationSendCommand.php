<?php
namespace Mamba\EncountersBundle\Command;

use Core\LeveldbBundle\LeveldbException;
use Mamba\EncountersBundle\Script\CronScript;

use Mamba\EncountersBundle\EncountersBundle;
use Mamba\EncountersBundle\Helpers\SearchPreferences;
use Mamba\EncountersBundle\Helpers\Declensions;
use Mamba\EncountersBundle\Helpers\Popularity;
use PDO;

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
        SCRIPT_NAME = "cron:notification:send",

        /**
         * SQL-запрос на получение данных для нотифицированияё
         *
         *
         * @var str
         */
        GET_NOTIFICATIONS_SQL = "
            SELECT
                info.user_id as user_id,
                lastaccess.lastaccess as lastaccess,
                lastonline.last_online as last_online,
                notifications.last_notification_sent as last_notification_sent,
                notifications.last_notification_metrics as last_notification_metrics,
                counters.visitors_unread as visitors_unread,
                counters.mutual_unread as mutual_unread,
                counters.messages_unread as messages_unread,
                energy.energy as energy,
                traffic_sources.from_notifications_count,
                traffic_sources.last_from_notifications,
                sum(billing.amount) as amount
            FROM
                UserInfo info
            LEFT JOIN
                UserLastAccess lastaccess
            ON
                lastaccess.user_id = info.user_id
            LEFT JOIN
                UserLastOnline lastonline
            ON
                lastonline.user_id = info.user_id
            LEFT JOIN
                UserNotifications notifications
            ON
                notifications.user_id = info.user_id
            LEFT JOIN
                UserCounters counters
            ON
                counters.user_id = info.user_id
            LEFT JOIN
                UserEnergy energy
            ON
                energy.user_id = info.user_id
            LEFT JOIN
                UserTrafficSources traffic_sources
            ON
                traffic_sources.user_id = info.user_id
            LEFT JOIN
                Billing billing
            ON
                billing.user_id = info.user_id
            LEFT JOIN
                UserPhotos photos
            ON
                photos.user_id = info.user_id
            WHERE
                info.is_app_user = 1 AND
                lastaccess <> 0 AND
                photos.count <> 0 AND
                (
                    counters.visitors_unread <> 0 OR
                    counters.mutual_unread <> 0
                )
                AND
                (
                    lastaccess.lastaccess > UNIX_TIMESTAMP() - 1*60*60*24 OR
                    lastonline.last_online > UNIX_TIMESTAMP() - 1*60*60*24
                )
            GROUP BY
                info.user_id",

        /**
         * SQL-запрос на получение данных о последних голосованиях за пользователя
         *
         * @var str
         */
        SQL_GET_LAST_DECISIONS = "
            SELECT
                *
            FROM
                Decisions
            WHERE
                current_user_id = :current_user_id
            ORDER BY
                changed DESC
            LIMIT
                2",

        SQL_USER_NOTIFICATIONS_UPDATE = "
            INSERT INTO
                UserNotifications
            SET
                user_id = :user_id,
                last_notification_sent = NOW(),
                last_notification_message = :message,
                last_notification_metrics = :metrics,
                notifications_count = 1
            ON DUPLICATE KEY UPDATE
                last_notification_sent = NOW(),
                last_notification_message = :message,
                last_notification_metrics = :metrics,
                notifications_count = notifications_count + 1
        "
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $this->log("Performing notifications", 48);
        $Query = $this->getMySQL()->getQuery(self::GET_NOTIFICATIONS_SQL);

        if ($Query->execute()->getResult()) {
            $this->log('Selected ' . $Query->getStatement()->rowCount() . ' rows..', 64);

            while ($task = $Query->fetch()) {
                $task['user_id'] = (int) $task['user_id'];
                $task['lastaccess'] = $task['lastaccess'];
                $task['last_online'] = $task['last_online'];
                $task['last_notification_sent'] = $task['last_notification_sent'];
                $task['visitors_unread'] = (int) $task['visitors_unread'];
                $task['mutual_unread'] = (int) $task['mutual_unread'];
                $task['messages_unread'] = (int) $task['messages_unread'];

                $task['energy'] = (int) $task['energy'];
                $task['amount'] = (int) $task['amount'];

                try {
                    $this->processTask($task);
                } catch (\Exception $e) {
                    $this->log($e->getCode() . ": " . $e->getMessage(), 16);
                }
            }
        } else {
            $this->log("SQL error", 16);
        }

        $this->log("Bye");
    }

    /**
     * Процессит таск
     *
     * @param $task
     */
    private function processTask($task) {
        $this->log(var_export($task, true), 48);

        /** Целевая аудитория */
        $currentNotificationMetrics = "v:{$task['visitors_unread']},m:{$task['mutual_unread']}";
        $lastNotificationMetrics = $task['last_notification_metrics'];

        if
        (
            $task['visitors_unread'] &&
            (
                ($currentNotificationMetrics != $lastNotificationMetrics) ||
                (time() - $task['last_notification_sent'] >= 86400 * 3)
            )
        ) {
            if ($message = $this->getNotifyMessage($task['user_id'], $task['visitors_unread'], $task['mutual_unread'])) {
                $this->log("Trying to send notification for user_id={$task['user_id']}");
                $this->log($message);
                if ($result = $this->getMamba()->Notify()->sendMessage($task['user_id'], $message, $extra = 'ref-notifications')) {
                    if (isset($result['count']) && $result['count']) {
                        $this->getStatsHelper()->incr('notify');

                        $this->getVariablesHelper()->set($task['user_id'], 'last_notification_sent', time());
                        $this->getVariablesHelper()->set($task['user_id'], 'last_notification_metrics', $currentNotificationMetrics);

                        /**
                         * Нужно записать для юзера инфу в табличку:
                         * 1) когда нотификация ушла
                         * 2) ее текст
                         * 3) увеличить счетчик нотификаций
                         *
                         * @author shpizel
                         */
                        $this->getMySQL()->getQuery(self::SQL_USER_NOTIFICATIONS_UPDATE)->bindArray([
                            ['user_id', $task['user_id'], PDO::PARAM_INT],
                            ['message', $message, PDO::PARAM_STR],
                            ['metrics', $currentNotificationMetrics, PDO::PARAM_LOB],
                        ])->execute();

                        $this->log("Notification send SUCCESS", 64);
                    } else {
                        $this->log("Notification send FAILED", 16);
                    }
                } else {
                    $this->log("Notification send FAILED", 16);
                }
            } else {
                $this->log("Could not get notification message for user_id={$task['user_id']}", 16);
            }
        } else {
            $this->log("Notification metrics is equal or no visitors unread for user_id={$task['user_id']}");
        }
    }

    /**
     * Генерирует и возвращает сообщение от менеджера сообщений для карент юзера
     *
     * Марина (25) оценила вас в приложении «Выбиратор»!
     * Марина (25) и Ксения (21) оценили вас в приложении «Выбиратор»!
     * Марина (25), Ксения (21) и еще 23 пользователя оценили вас в приложении «Выбиратор»!
     * Марина (25) оценила вас в приложении «Выбиратор», плюс у вас одна новая взаимная симпатия :)
     * Марина (25) оценила вас в приложении «Выбиратор», плюс у вас 5 новых взаимных симпатий :)
     *
     * @param int $currentUserId
     * @return str
     */
    private function getNotifyMessage($currentUserId, $visitorsUnread, $mutualUnread) {
        if ($webUsers = $this->getWebUsersInfo($currentUserId)) {
            if ($visitorsUnread && !$mutualUnread) {
                if ($visitorsUnread == 1 || count($webUsers) < 2) {
                    //Марина (25) оценила вас в приложении «Выбиратор»!

                    $webUser = array_shift($webUsers);
                    $message = $webUser['name'];
                    if ($webUser['age']) {
                        $message.=" (" . $webUser['age'] . ")";
                    }

                    if ($webUser['gender'] == 'F') {
                        $message.= " оценила вас в приложении «Выбиратор»!";
                    } else {
                        $message.= " оценил вас в приложении «Выбиратор»!";
                    }

                } elseif ($visitorsUnread == 2 && count($webUsers) >= 2) {
                    //Марина (25) и Ксения (21) оценили вас в приложении «Выбиратор»!
                    $message = "";
                    $index = 0;
                    while ($webUser = array_shift($webUsers)) {
                        if ($index) {
                            $message.= " и ";
                        }

                        $message .= $webUser['name'];
                        if ($webUser['age']) {
                            $message.=" (" . $webUser['age'] . ")";
                        }

                        if ($index >= 1) {
                            break;
                        }

                        $index++;
                    }

                    if ($message) {
                        $message .= " оценили вас в приложении «Выбиратор»!";
                    }
                } else {
                    //Марина (25), Ксения (21) и еще 23 пользователя оценили вас в приложении «Выбиратор»!

                    $message = "";
                    $index = 0;
                    while ($webUser = array_shift($webUsers)) {
                        if ($index) {
                            $message.= ", ";
                        }

                        $message .= $webUser['name'];
                        if ($webUser['age']) {
                            $message.=" (" . $webUser['age'] . ")";
                        }

                        if ($index >= 1) {
                            break;
                        }

                        $index++;
                    }

                    if ($message) {
                        $message .= " и еще " . ($visitorsUnread - 2) . " " . Declensions::get($visitorsUnread - 2, "пользователь", "пользователя", "пользователей");
                        $message .= " оценили вас в приложении «Выбиратор»!";
                    }
                }
            } elseif ($mutualUnread && !$visitorsUnread) {
                $message = "У вас $mutualUnread " . Declensions::get($mutualUnread, "новая взаимная симпатия", "новые взаимные симпатии", "новых взаимных симпатий") . " в приложении «Выбиратор»!";
            } elseif ($mutualUnread && $visitorsUnread) {
                $webUser = array_shift($webUsers);
                $message = $webUser['name'];
                if ($webUser['age']) {
                    $message.=" (" . $webUser['age'] . ")";
                }

                if ($visitorsUnread > 1) {
                    $message .= " и еще " . ($visitorsUnread - 1) . " " . Declensions::get($visitorsUnread - 2, "пользователь", "пользователя", "пользователей") . " оценили вас в приложении «Выбиратор»";
                } else {
                    if ($webUser['gender'] == 'F') {
                        $message.= " оценила вас в приложении «Выбиратор»";
                    } else {
                        $message.= " оценил вас в приложении «Выбиратор»";
                    }
                }


                if ($mutualUnread) {
                    $message .= ", плюс у вас $mutualUnread " . Declensions::get($mutualUnread, "новая взаимная симпатия", "новые взаимные симпатии", "новых взаимных симпатий") . " :)";
                }
            }

            if (isset($message)) {
                return $message;
            }
        } else {
            $this->getCountersHelper()->set($currentUserId, 'visitors_unread', 0);
            $this->getCountersHelper()->set($currentUserId, 'mutual_unread', 0);
        }
    }

    private function getWebUsersInfo($currentUserId) {
        $stmt = $this->getDoctrine()->getConnection()->prepare(self::SQL_GET_LAST_DECISIONS);
        $stmt->bindParam('current_user_id', $currentUserId);
        if ($stmt->execute()) {
            $webUsers = array();
            while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $webUsers[] = (int) $item['web_user_id'];
            }

            if ($webUsers && ($result = $this->getUsersHelper()->getInfo($webUsers, ['info']))) {
                $ret = array();

                foreach ($result as $item) {
                    $ret[$item['info']['user_id']] = array(
                        'name'   => $item['info']['name'],
                        'age'    => $item['info']['age'],
                        'gender' => $item['info']['gender'],
                    );
                }

                return $ret;
            }
        }
    }

    /**
     * Возвращает имя пользователя
     *
     * @param int $userId
     * @return string|null
     */
    private function getUserNameById($userId) {
        if ($anketa = $this->getUsersHelper()->getInfo($userId, ['info'])) {
            $name = $anketa['info']['name'];

            return $name;
        }
    }
}