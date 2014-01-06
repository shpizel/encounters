<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Controller\MessengerController;
use Mamba\EncountersBundle\Script\Script;
use Mamba\EncountersBundle\Tools\Gifts\Gifts;
use Mamba\EncountersBundle\Helpers\Messenger\Message;
use Mamba\EncountersBundle\EncountersBundle;
use Mamba\EncountersBundle\Helpers\SearchPreferences;

use PDO;

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
        print_r($this->getUsersHelper()->getInfo([560015854,679658402]));
        exit();
        var_dump($this->getMamba()->Anketa()->getInfo('shpizel'));

        exit();
        $VariablesHepler = $this->getVariablesHelper();
        $counter = 0;

        while ($users = $this->getUsers(5000)) {
            if ($variables = $VariablesHepler->getMulti(
                $users,
                ['last_notification_sent', 'last_notification_metrics']
            )) {
                $sql = ["INSERT INTO `Encounters`.`UserNotifications`(`user_id`, `last_notification_sent`, `last_notification_metrics`) VALUES"];
                foreach ($variables as $userId=>$vars) {
                    $_lastNotificationSent = (int) $vars['last_notification_sent'];
                    $_lastNotificationMetrics = $vars['last_notification_metrics'];

                    if ($_lastNotificationSent) {
                        $sql[] = "({$userId}, FROM_UNIXTIME({$_lastNotificationSent}), '{$_lastNotificationMetrics}'),";
                    }
                }

                $sql[count($sql) - 1] = substr($sql[count($sql) - 1], 0, -1);

                $sql[] = ";";

                $sql = implode("\n", $sql);

                var_dump($this->getEntityManager()->getConnection()->exec($sql));

                $counter+=5000;
                $this->log($counter);
            }
        }
    }

    /**
     * Возвращает айдишники пользователей у который есть поисковые предпочтения (т.е. активные)
     *
     * @param $count
     * @return array
     */
    private function getUsers($count) {
        $defaultLastGetUsersKey =
            sprintf(
                str_replace('%d', '%s', SearchPreferences::LEVELDB_USER_SEARCH_PREFERENCES),
                null
            )
        ;

        if (!isset($this->lastGetUsersKey)) {
            $this->lastGetUsersKey = $defaultLastGetUsersKey;
        }

        $Leveldb = $this->getLeveldb();
        $Request = $Leveldb->get_range($this->lastGetUsersKey, null, $count);
        $Leveldb->execute();

        $users = array();
        if ($result = $Request->getResult()) {
            foreach ($result as $key=>$val) {
                if (strpos($key, $defaultLastGetUsersKey) !== false && $key != $this->lastGetUsersKey) {
                    $users[] = (int) substr($key, strlen($defaultLastGetUsersKey));
                }
            }

            if ($users) {
                $this->lastGetUsersKey = $defaultLastGetUsersKey . $users[count($users) - 1];
                return $users;
            }
        }
    }
}