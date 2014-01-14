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
        $this->log(1);
        var_dump($this->getMamba()->nocache()->Anketa()->isOnline([220234, 471319, 489860, 508227, 559639, 560671, 755130, 1029053, 1537847, 1542236, 2035502, 2085935, 2346600, 2834264, 3129819, 3134614, 3140871, 3646014, 3921115, 4683682, 5712512, 7330538, 8077093, 9325757, 10206985, 10424376, 11512622, 12100860, 12581030, 13954131]));
        $this->log(2);
        exit();
        $MySQL = $this->getMySQL();
        $MySQL->exec("set @web_user_id := 560015854;select @web_user_age := `age` from UserInfo where user_id = @web_user_id;");
        print_r($MySQL->getQuery("select @web_user_age;")->execute()->fetch());
        exit();
        $counter = 0;
        $filename = '/home/shpizel/interests.sql';
        @unlink($filename);
        $Query = $this->getMySQL()->getQuery("select * from UserInterests");
        if ($Query->execute()->getResult()) {
            while ($row = $Query->fetch()) {
                $userId = (int) $row['user_id'];
                $albums = json_decode($row['interests'], true);
                $interests = count($albums);

                $sql = "update `UserInterests` set `count`={$interests} where `user_id`={$userId} limit 1;\n";
                file_put_contents($filename, $sql, FILE_APPEND);

                $counter++;
                $this->log($counter, -1);
            }
        }

        exit();
        while ($users = $this->getUsers(5000)) {
            foreach ($users as $userId) {
                $this->getGearman()->getClient()->doLowBackground(
                    EncountersBundle::GEARMAN_DATABASE_USERS_SEARCH_PREFERENCES_UPDATE_FUNCTION_NAME,
                    serialize(
                        array(
                            'user_id' => $userId,
                            'time'    => time(),
                        )
                    )
                );
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