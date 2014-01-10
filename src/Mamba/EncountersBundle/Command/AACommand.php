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
        print_r($Mamba = $this->getMamba()->Anketa()->getInfo([1131022170, 560015854], ['about']));
        exit();
        $Mamba->multi();
        $ids = [1131022170, 547075878, 1100187593, 1157528928, 1107723185, 967122486, 958635043, 1157174077, 1051193886, 959872398, 940694966, 578018918, 1089864512, 949371453, 1045380262, 1168672072];
        foreach ($ids as $id) {
            $Mamba->Photos()->getAlbums($id);
        }
        print_r($Mamba->exec());
        exit();
        print_r($this->getMamba()->Anketa()->getInfo(
            [1131022170,547075878,1100187593,1157528928,1107723185,967122486,958635043,1157174077,1051193886,959872398,940694966,578018918,1089864512,949371453,1045380262,1168672072,935481298,965846775,1098669906,1165721049,1077770892,751586309,1160045763,1092205447,665551695,1118913277,600245177,753794805,698619057,685580314,1160932924,1088820506],
            ['about'])
        );
        exit();
        var_dump(
            $this->getMySQL()->getQuery('select * from UserInfo where user_id = :user_id')->bind("user_id", 560015854)->execute()->fetch()
        );
        exit();
        var_dump($this->getRedis()->zRange('sss', 1,2));
        exit();

        $VariablesHepler = $this->getVariablesHelper();
        $counter = 0;

        while ($users = $this->getUsers(5000)) {
            $users = array_chunk($users, 16);
            foreach ($users as $chunk) {
                $this->getGearman()->getClient()->doLowBackground(
                    EncountersBundle::GEARMAN_DATABASE_USERS_UPDATE_FUNCTION_NAME,
                    serialize($dataArray = array(
                        'users' => $chunk,
                        'time'  => time(),
                    ))
                );
            }
//            if ($variables = $VariablesHepler->getMulti(
//                $users,
//                ['last_notification_sent', 'last_notification_metrics']
//            )) {
//                $sql = ["INSERT INTO `Encounters`.`UserNotifications`(`user_id`, `last_notification_sent`, `last_notification_metrics`) VALUES"];
//                foreach ($variables as $userId=>$vars) {
//                    $_lastNotificationSent = (int) $vars['last_notification_sent'];
//                    $_lastNotificationMetrics = $vars['last_notification_metrics'];
//
//                    if ($_lastNotificationSent) {
//                        $sql[] = "({$userId}, FROM_UNIXTIME({$_lastNotificationSent}), '{$_lastNotificationMetrics}'),";
//                    }
//                }
//
//                $sql[count($sql) - 1] = substr($sql[count($sql) - 1], 0, -1);
//
//                $sql[] = ";";
//
//                $sql = implode("\n", $sql);
//
//                var_dump($this->getEntityManager()->getConnection()->exec($sql));
//
//                $counter+=5000;
//                $this->log($counter);
//            }
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