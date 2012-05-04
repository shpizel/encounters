<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Command\CronScript;
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
         * SQL-запрос на получение данных для нотифицирования
         *
         * @var str
         */
        GET_NOTIFICATIONS_SQL = "
            SELECT
                n.*,
                e.energy as `energy`,
                sum(b.amount) as `amount`
            FROM
                `Notifications` n
            LEFT JOIN Energy e
                ON e.user_id = n.user_id
            LEFT JOIN Billing b
                ON b.user_id = n.user_id
            WHERE
                (NOT n.lastaccess OR
                UNIX_TIMESTAMP(NOW()) - n.lastaccess > 8*60*60) AND
                (NOT n.last_notification_sent OR
                UNIX_TIMESTAMP(NOW()) - n.last_notification_sent > 12*60*60) AND
                n.visitors_unread > 0
            GROUP BY
                user_id
            ORDER BY
                last_notification_sent,
                last_outgoing_decision,
                amount DESC",

        /**
         * SQL-запрос на получение данных для добавления энергии
         *
         * @var str
         */
        GET_ENERGY_SQL = "
            SELECT
                n.*,
                e.energy as `energy`,
                sum(b.amount) as `amount`
            FROM
                `Notifications` n
            LEFT JOIN Energy e
                ON e.user_id = n.user_id
            LEFT JOIN Billing b
                ON b.user_id = n.user_id
            WHERE
                (NOT n.lastaccess OR
                UNIX_TIMESTAMP(NOW()) - n.lastaccess > 7*24*60*60) AND
                not n.visitors_unread AND
                NOT energy
            GROUP BY
                user_id"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        list($Redis, $Memcache, $DB, $Mamba, $Variables) = array(
            $this->getRedis(),
            $this->getMemcache(),
            $this->getDoctrine()->getConnection(),
            $this->getMamba(),
            $this->getVariablesObject(),
        );

        $DB->prepare("TRUNCATE Notifications")->execute();

        $usedVariables = array(
            'lastaccess',
            'last_outgoing_decision',
            'last_notification_sent',
            'last_notification_metrics',
        );

        $appUsers  = $Redis->hKeys(SearchPreferences::REDIS_HASH_USERS_SEARCH_PREFERENCES_KEY);
        //shuffle($appUsers);
        //$appUsers = array_slice($appUsers, 0, 1000);

        $this->log("Users: <info>" . count($appUsers) . "</info>");

        $appUsers = array_chunk($appUsers, 1024);
        $stage = 0;

        while ($chunk = array_shift($appUsers)) {

            $dataArray = array();

            $stage++;
            $this->log("Stage {$stage}", 64);

            $Redis->multi();

            foreach ($chunk as $userId) {
                $Redis->hGetAll("variables_by_{$userId}");
                $dataArray[$userId] = array();
            }

            if ($result = $Redis->exec()) {

                foreach ($result as $key=>$item) {
                    foreach ($item as $variable => $data) {
                        if (in_array($variable, $usedVariables)) {
                            $data = json_decode($data, true);
                            $dataArray[$chunk[$key]][$variable] = $data['data'];
                        }
                    }
                }

                foreach ($dataArray as $userId=>$variables) {
                    foreach ($usedVariables as $name) {
                        if (!isset($variables[$name])) {
                            $dataArray[$userId][$name] = null;
                        }
                    }

                    $dataArray[$userId]['lastaccess'] = max(
                        $dataArray[$userId]['lastaccess'],
                        $dataArray[$userId]['last_outgoing_decision']
                    );

                    $dataArray[$userId]['user_id'] = $userId;
                    $dataArray[$userId]['visitors_unread'] = $this->getCountersObject()->get($userId, 'visitors_unread');
                    $dataArray[$userId]['mutual_unread'] = $this->getCountersObject()->get($userId, 'mutual_unread');
                }

                $this->log("Fetch variables from redis completed", 48);

                $this->log("Storing data to database..");
                $stmt = $DB->prepare(
                    "INSERT INTO
                        Notifications
                    SET
                        user_id    = :user_id,
                        lastaccess = :lastaccess,
                        last_outgoing_decision = :last_outgoing_decision,
                        last_notification_sent = :last_notification_sent,
                        last_notification_metrics = :last_notification_metrics,
                        visitors_unread = :visitors_unread,
                        mutual_unread = :mutual_unread
                    ON DUPLICATE KEY UPDATE
                        lastaccess = :lastaccess,
                        last_outgoing_decision = :last_outgoing_decision,
                        last_notification_sent = :last_notification_sent,
                        last_notification_metrics = :last_notification_metrics,
                        visitors_unread = :visitors_unread,
                        mutual_unread = :mutual_unread
                    "
                );

                foreach ($dataArray as $userId=>$variables) {
                    $stmt->bindParam('user_id', $variables['user_id']);
                    $stmt->bindParam('lastaccess', $variables['lastaccess']);
                    $stmt->bindParam('last_outgoing_decision', $variables['last_outgoing_decision']);
                    $stmt->bindParam('last_notification_sent', $variables['last_notification_sent']);
                    $stmt->bindParam('last_notification_metrics', $variables['last_notification_metrics']);
                    $stmt->bindParam('visitors_unread', $variables['visitors_unread']);
                    $stmt->bindParam('mutual_unread', $variables['mutual_unread']);

                    $stmt->execute();
                }

                $this->log("Preparation completed", 64);
            } else {
                $this->log("Redis multiget error", 16);
            }
        }

        $dataArray = null;

        $this->log("Performing energy", 48);
        $stmt = $DB->prepare(self::GET_ENERGY_SQL);
        if ($stmt->execute()) {
            while ($task = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $task['user_id'] = (int) $task['user_id'];
                $task['lastaccess'] = (int) $task['lastaccess'];
                $task['last_outgoing_decision'] = (int) $task['last_outgoing_decision'];
                $task['last_notification_sent'] = (int) $task['last_notification_sent'];
                $task['visitors_unread'] = (int) $task['visitors_unread'];
                $task['mutual_unread'] = (int) $task['mutual_unread'];

                $task['energy'] = (int) $task['energy'];
                $task['amount'] = (int) $task['amount'];

                try {
                    $this->processEnergyUpdate($task);
                } catch (\Exception $e) {
                    $this->log($e->getCode() . ": " . $e->getMessage(), 16);
                }
            }
        } else {
            $this->log("SQL error", 16);
        }

        $this->log("Performing notifications", 48);
        $stmt = $DB->prepare(self::GET_NOTIFICATIONS_SQL);
        if ($stmt->execute()) {
            while ($task = $stmt->fetch(PDO::FETCH_ASSOC)) {

                $task['user_id'] = (int) $task['user_id'];
                $task['lastaccess'] = (int) $task['lastaccess'];
                $task['last_outgoing_decision'] = (int) $task['last_outgoing_decision'];
                $task['last_notification_sent'] = (int) $task['last_notification_sent'];
                $task['visitors_unread'] = (int) $task['visitors_unread'];
                $task['mutual_unread'] = (int) $task['mutual_unread'];

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
    }

    private function processEnergyUpdate($task) {
        $this->log(var_export($task, true), 48);
        $this->getEnergyObject()->set($task['user_id'], Popularity::$levels[4]);
        $this->log("Added energy to {$task['user_id']}", 64);
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

        if ($task['visitors_unread'] && ($currentNotificationMetrics != $lastNotificationMetrics || (time() - $task['last_notification_sent'] >= 7*86400))) {
            if ($message = $this->getNotifyMessage($task['user_id'], $task['visitors_unread'], $task['mutual_unread'])) {
                if ($result = $this->getMamba()->Notify()->sendMessage($task['user_id'], $message)) {
                    if (isset($result['count']) && $result['count']) {
                        $this->log($message, 64);
                        $this->getStatsObject()->incr('notify');

                        $this->getVariablesObject()->set($task['user_id'], 'last_notification_sent', time());
                        $this->getVariablesObject()->set($task['user_id'], 'last_notification_metrics', $currentNotificationMetrics);
                    } else {
                        $this->log($message, 16);
                    }
                } else {
                    $this->log($message, 16);
                }
            } else {
                $this->log("Could not get notification message");
            }
        } else {
            $this->log("Notification metrics is equal or no visitors unread");
        }
    }

    /**
     * Генерирует и возвращает сообщение от менеджера сообщений для карент юзера
     *
     * @param int $currentUserId
     * @return str
     */
    private function getNotifyMessage($currentUserId, $visitorsUnread, $mutualUnread) {
        if ($anketa = $this->getMamba()->Anketa()->getInfo($currentUserId)) {
            $name = $anketa[0]['info']['name'];
            $name = explode(" ", $name);
            $name = array_shift($name);

            if ($visitorsUnread && !$mutualUnread) {
                if ($visitorsUnread == 1) {
                    $message = "$name, у вас появилась новая оценка в приложении «Выбиратор»!";
                } else {
                    $message = "$name, у вас $visitorsUnread " . Declensions::get($visitorsUnread, "новая оценка", "новые оценки", "новых оценок") . " в приложении «Выбиратор»!";
                }
            } elseif ($mutualUnread && !$visitorsUnread) {
                $message = "$name, вам ответили взаимностью в приложении «Выбиратор»!";
                $message = "$name, у вас $mutualUnread " . Declensions::get($mutualUnread, "новая взаимная симпатия", "новые взаимные симпатии", "новых взаимных симпатий") . " в приложении «Выбиратор»!";
            } elseif ($mutualUnread && $visitorsUnread) {
                $message = "$name, у вас $visitorsUnread " . Declensions::get($visitorsUnread, "новая оценка", "новые оценки", "новых оценок") . " и $mutualUnread " . Declensions::get($mutualUnread, "новая взаимная симпатия", "новые взаимные симпатии", "новых взаимных симпатий") . " в приложении «Выбиратор»!";
            }

            if (isset($message)) {
                return $message;
            }
        }
    }
}