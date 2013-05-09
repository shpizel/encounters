<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Controller\MessengerController;
use Mamba\EncountersBundle\Script\Script;
use Mamba\EncountersBundle\Tools\Gifts\Gifts;
use Mamba\EncountersBundle\Helpers\Messenger\Message;
use Mamba\EncountersBundle\EncountersBundle;
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
        var_dump($this->getMamba()->getReady());
        exit();
        $html = "<div>a <textarea cols=\"aaa\"></textarea>aaa" . '<br><img   class="smile s-1" src=\'/bundles/encounters/images/pixel.gif\'><img><br /></div>';
        echo MessengerController::cleanHTMLMessage($html);

        exit();
        foreach (range(0, 10) as $i) {
            $this->log($i, 1, -1);
            usleep(10*1000);
        }
        $this->log( "OK". PHP_EOL, -1);
        exit();
        print_r($this->getMamba()->Anketa()->isOnline(1117415127));
        exit();
        $VariablesHelper = $this->getVariablesHelper();
        $NotificationsHelper = $this->getNotificationsHelper();
        $Redis = $this->getRedis();
        $Leveldb = $this->getLeveldb();

        $counter = 0;
        while ($users = $this->getUsers(1024)) {
            $notificationsHidden = $VariablesHelper->getMulti($users, ['notification_hidden']);
            $Leveldb->clearMetrics();

            foreach ($users as $userId) {
                if (isset($notificationsHidden[$userId]) && ($notificationsHidden[$userId]['notification_hidden'] == 1)) {
                    $NotificationsHelper->add((int) $userId, 'Ура! Теперь можно просматривать фотографии в анкетах внутри приложения!');
                    $Redis->clearMetrics();

                    $this->log(++$counter, -1);
                }
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