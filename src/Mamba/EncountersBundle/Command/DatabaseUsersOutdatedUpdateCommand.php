<?php
namespace Mamba\EncountersBundle\Command;

use Core\ScriptBundle\CronScriptException;
use Mamba\EncountersBundle\Helpers\Users;
use Mamba\EncountersBundle\Script\CronScript;

use Mamba\EncountersBundle\EncountersBundle;

/**
 * DatabaseUsersOutdatedUpdateCommand
 *
 * @package EncountersBundle
 */
class DatabaseUsersOutdatedUpdateCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Updates outdated users",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:database:users:outdated:update",

        /**
         * @var str
         */
        SQL_GET_OUTDATED_USERS = "
            SELECT
                user_id,
                is_app_user,
                IF(
                    is_app_user = 1,
                    DATE_ADD(changed, INTERVAL 7 DAY),
                    DATE_ADD(changed, INTERVAL 14 DAY)
                ) as `expired`
            FROM
                UserInfo
            having
                `expired` < NOW()"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $MySQL = $this->getMySQL();

        $Query = $MySQL->getQuery(self::SQL_GET_OUTDATED_USERS);
        if ($Query->execute()->getResult()) {
            $users = [];
            while ($row = $Query->fetch()) {
                $users[] = (int) $row['user_id'];

                if (count($users) >= 100) {
                    $this->getGearman()->getClient()->doLowBackground(
                        EncountersBundle::GEARMAN_DATABASE_USERS_UPDATE_FUNCTION_NAME,
                        serialize($dataArray = array(
                            'users' => $users,
                            'time'  => time(),
                        ))
                    );

                    $users = [];
                }
            }

            if ($users) {
                /** Отправим задачу в очередь на заполнение БД */
                $this->getGearman()->getClient()->doLowBackground(
                    EncountersBundle::GEARMAN_DATABASE_USERS_UPDATE_FUNCTION_NAME,
                    serialize($dataArray = array(
                        'users' => $users,
                        'time'  => time(),
                    ))
                );
            }
        }

        $this->log("Bye", 48);
    }
}