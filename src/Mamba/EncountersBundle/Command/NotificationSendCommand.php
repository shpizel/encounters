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
                n.last_online AND
                n.last_notification_sent < n.last_online AND
                (NOT n.last_notification_sent OR
                UNIX_TIMESTAMP(NOW()) - n.last_notification_sent > 8*60*60) AND
                n.visitors_unread > 0 AND
                n.last_notification_metrics <> concat('v:', n.visitors_unread, ',m:', n.mutual_unread)
            GROUP BY
                user_id
            ORDER BY
                last_online DESC,
                lastaccess DESC, -- оч странная хуйня пиздец (было ASC)
                last_notification_sent ASC,
                visitors_unread DESC,
                mutual_unread DESC",

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
                NOT n.visitors_unread AND
                NOT energy
            GROUP BY
                user_id",

        /**
         * SQL-запрос на получение данных о последних голосованиях за пользователя
         *
         * @var str
         */
        GET_LAST_DECISIONS = "
            SELECT
                *
            FROM
                Decisions
            WHERE
                current_user_id = :current_user_id
            ORDER BY
                changed DESC
            LIMIT
                2"
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
//        shuffle($appUsers);
//        $appUsers = array_slice($appUsers, 0, 1000);

        $this->log("Users: <info>" . count($appUsers) . "</info>");

        $appUsers = array_chunk($appUsers, 1000);
        $stage = 0;

        while ($chunk = array_shift($appUsers)) {

            $dataArray = array();

            $stage++;
            $this->log("Stage {$stage}", 64);

            $lastOnlineChunk = array_chunk($chunk, 30);
            $anketaChunk = array_chunk($chunk, 100);

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

                $this->getMamba()->multi();
                foreach ($lastOnlineChunk as $__chunk) {
                    $this->getMamba()->Anketa()->isOnline(array_map(function($i){return (int)$i;},$__chunk));
                }
                if ($onlineCheckResult = $this->getMamba()->exec(35)) {
                    foreach ($onlineCheckResult as $onlineCheckResultChunk) {
                        foreach ($onlineCheckResultChunk as $_anketa) {
                            if (isset($dataArray[$_anketa['anketa_id']])) {
                                $dataArray[$_anketa['anketa_id']]['last_online'] = $_anketa['is_online'] == 1 ? time() : $_anketa['is_online'];
                            }
                        }
                    }
                }

                $this->getMamba()->multi();
                foreach ($anketaChunk as $__chunk) {
                    $this->getMamba()->Anketa()->getInfo(array_map(function($i){return (int)$i;},$__chunk));
                }
                if ($anketaResult = $this->getMamba()->exec(10)) {
                    foreach ($anketaResult as $anketaResultChunk) {
                        foreach ($anketaResultChunk as $_anketa) {

                            if (isset($_anketa['info']) && isset($_anketa['info']['is_app_user']) && isset($_anketa['info']['oid']) && isset($dataArray[$_anketa['info']['oid']])) {
                                $dataArray[$_anketa['info']['oid']]['is_app_user'] = $_anketa['info']['is_app_user'];
                            }
                        }
                    }
                }

                $this->log("Fetch variables from redis completed", 48);
                $this->log("Storing data to database..");

                foreach ($dataArray as $userId => $variables) {
                    if (!(isset($variables['is_app_user']) && $variables['is_app_user'])) {
                        continue;
                    }

                    $variables['last_online'] = isset($variables['last_online']) ? $variables['last_online'] : null;

                    $sql = "INSERT INTO
                        Notifications
                    SET
                        user_id    = ':user_id',
                        lastaccess = ':lastaccess',
                        last_online = ':last_online',
                        last_outgoing_decision = ':last_outgoing_decision',
                        last_notification_sent = ':last_notification_sent',
                        last_notification_metrics = ':last_notification_metrics',
                        visitors_unread = ':visitors_unread',
                        mutual_unread = ':mutual_unread'
                    ON DUPLICATE KEY UPDATE
                        lastaccess = ':lastaccess',
                        last_online = ':last_online',
                        last_outgoing_decision = ':last_outgoing_decision',
                        last_notification_sent = ':last_notification_sent',
                        last_notification_metrics = ':last_notification_metrics',
                        visitors_unread = ':visitors_unread',
                        mutual_unread = ':mutual_unread'"
                    ;

                    $sql = str_replace(':user_id', $variables['user_id'], $sql);
                    $sql = str_replace(':lastaccess', $variables['lastaccess'], $sql);
                    $sql = str_replace(':last_online', $variables['last_online'], $sql);
                    $sql = str_replace(':last_outgoing_decision', $variables['last_outgoing_decision'], $sql);
                    $sql = str_replace(':last_notification_sent', $variables['last_notification_sent'], $sql);
                    $sql = str_replace(':last_notification_metrics', $variables['last_notification_metrics'], $sql);
                    $sql = str_replace(':visitors_unread', $variables['visitors_unread'], $sql);
                    $sql = str_replace(':mutual_unread', $variables['mutual_unread'], $sql);

                    //$this->log($sql);
                    $DB->exec($sql);
                }

                $this->log("Preparation completed", 64);
            } else {
                $this->log("Redis multiget error", 16);
            }
        }

        $dataArray = $result = null;

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
        }
    }

    private function getWebUsersInfo($currentUserId) {
        $stmt = $this->getDoctrine()->getConnection()->prepare(self::GET_LAST_DECISIONS);
        $stmt->bindParam('current_user_id', $currentUserId);
        if ($stmt->execute()) {
            $webUsers = array();
            while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $webUsers[] = (int) $item['web_user_id'];
            }

            if ($webUsers && ($result = $this->getMamba()->Anketa()->getInfo($webUsers))) {
                $ret = array();

                foreach ($result as $item) {
                    $ret[$item['info']['oid']] = array(
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
        if ($anketa = $this->getMamba()->Anketa()->getInfo($userId)) {
            $name = $anketa[0]['info']['name'];

            return $name;
        }
    }
}