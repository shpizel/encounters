<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Helpers\Users;
use Mamba\EncountersBundle\Script\CronScript;

use Mamba\EncountersBundle\EncountersBundle;

/**
 * DatabaseUsersUpdateCommand
 *
 * @package EncountersBundle
 */
class DatabaseUsersUpdateCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Updates users",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:database:users:update"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $worker = $this->getGearmanWorker();

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_DATABASE_USERS_UPDATE_FUNCTION_NAME, function($job) use($class) {
            return $class->updateUsers($job);
        });

        $iterations = $this->iterations;
        while
        (
            (!$this->getMemcache()->get("cron:stop") || (($stopCommandTimeStamp = (int) $this->getMemcache()->get("cron:stop")) && ($stopCommandTimeStamp < $this->started))) &&
            ((time() - $this->started < $this->lifetime) || !$this->lifetime) &&
            filemtime(__FILE__) < $this->started &&
            ((memory_get_usage() < $this->memory) || !$this->memory) &&
            $this->iterations-- &&
            $this->log(($iterations - $this->iterations) . " iteration:", 48) &&
            (@$worker->work() || $worker->returnCode() == GEARMAN_TIMEOUT)
        ) {
            if ($worker->returnCode() == GEARMAN_TIMEOUT) {
                $this->log("Timed out", 48);
                continue;
            } elseif ($worker->returnCode() != GEARMAN_SUCCESS) {
                $this->log("Failed", 16);
                break;
            } elseif ($worker->returnCode() == GEARMAN_SUCCESS) {
                $this->log("Completed", 64);
            }
        }

        $this->log("Bye", 48);
    }

    /**
     * Обновление таблицы энергий
     *
     * @param $job
     */
    public function updateUsers($job) {
        $workload = unserialize($job->workload());
        $users = $workload['users'];

        $this->log("Got task for <info>" . count($users) . "</info> users");

        $usersData = $this->getUsersHelper()->getInfo($users, true, 5);

        /** закешируем информацию */
        foreach ($usersData as $userId => $userData) {
            $this->getMemcache()->set('user_' . $userId . "_info", json_encode($userData), Users::USER_INFO_LIFETIME);
        }

        $DB = $this->getEntityManager()->getConnection();

        $sql = [
            "SET autocommit = 0;",
            //"SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE;",
            "START TRANSACTION;",
        ];

        foreach ($usersData as $userId=>$dataArray) {

            /**
             * Encounters.UserInfo = {
             *     'name',
             *     'gender',
             *     'age',
             *     'sign',
             *     'about',
             *     'is_app_user',
             *     'lang',
             * }
             */
            $dataArray['info']['is_app_user'] = (int) $dataArray['info']['is_app_user'];
            foreach ($dataArray['info'] as &$item) {
                $item = $DB->quote($item);
            }

            $sql[] = <<<EOL
INSERT INTO
    `Encounters`.`UserInfo`
SET
    `user_id`     = {$userId},
    `name`        = {$dataArray['info']['name']},
    `gender`      = {$dataArray['info']['gender']},
    `age`         = {$dataArray['info']['age']},
    `sign`        = {$dataArray['info']['sign']},
    `about`       = {$dataArray['info']['about']},
    `is_app_user` = {$dataArray['info']['is_app_user']},
    `lang`        = {$dataArray['info']['lang']}
ON DUPLICATE KEY UPDATE
    `name`        = {$dataArray['info']['name']},
    `gender`      = {$dataArray['info']['gender']},
    `age`         = {$dataArray['info']['age']},
    `sign`        = {$dataArray['info']['sign']},
    `about`       = {$dataArray['info']['about']},
    `is_app_user` = {$dataArray['info']['is_app_user']},
    `lang`        = {$dataArray['info']['lang']}
;
EOL;

            /**
             * Avatar = {
             *     'small_photo_url',
             *     'medium_photo_url',
             *     'square_photo_url',
             * }
             */
            foreach ($dataArray['avatar'] as &$item) {
                $item = $DB->quote($item);
            }

            $sql[] = <<<EOL
INSERT INTO
    `Encounters`.`UserAvatar`
SET
    `user_id`          = {$userId},
    `small_photo_url`  = {$dataArray['avatar']['small_photo_url']},
    `medium_photo_url` = {$dataArray['avatar']['medium_photo_url']},
    `square_photo_url` = {$dataArray['avatar']['square_photo_url']}
ON DUPLICATE KEY UPDATE
    `small_photo_url`  = {$dataArray['avatar']['small_photo_url']},
    `medium_photo_url` = {$dataArray['avatar']['medium_photo_url']},
    `square_photo_url` = {$dataArray['avatar']['square_photo_url']}
;
EOL;

            /**
             * Location = {
             *     'country_id',
             *     'country_name',
             *     'region_id',
             *     'region_name',
             *     'city_id',
             *     'city_name',
             * }
             */
            foreach ($dataArray['location']['country'] as &$item) {
                $item = $DB->quote($item);
            }

            foreach ($dataArray['location']['region'] as &$item) {
                $item = $DB->quote($item);
            }

            foreach ($dataArray['location']['city'] as &$item) {
                $item = $DB->quote($item);
            }

            $sql[] = <<<EOL
INSERT INTO
    `Encounters`.`UserLocation`
SET
    `user_id`      = {$userId},
    `country_id`   = {$dataArray['location']['country']['id']},
    `country_name` = {$dataArray['location']['country']['name']},
    `region_id`    = {$dataArray['location']['region']['id']},
    `region_name`  = {$dataArray['location']['region']['name']},
    `city_id`      = {$dataArray['location']['city']['id']},
    `city_name`    = {$dataArray['location']['city']['name']}
ON DUPLICATE KEY UPDATE
    `country_id`   = {$dataArray['location']['country']['id']},
    `country_name` = {$dataArray['location']['country']['name']},
    `region_id`    = {$dataArray['location']['region']['id']},
    `region_name`  = {$dataArray['location']['region']['name']},
    `city_id`      = {$dataArray['location']['city']['id']},
    `city_name`    = {$dataArray['location']['city']['name']}
;
EOL;
            /**
             * Interests = {
             *     'interests'
             * }
             */
            $dataArray['interests'] = $DB->quote(json_encode($dataArray['interests']));

            $sql[] = <<<EOL
INSERT INTO
    `Encounters`.`UserInterests`
SET
    `user_id`   = {$userId},
    `interests` = {$dataArray['interests']}
ON DUPLICATE KEY UPDATE
    `interests` = {$dataArray['interests']}
;
EOL;

            /**
             * Flags = {
             *     'is_vip',
             *     'is_real',
             *     'is_leader',
             *     'maketop',
             *     'is_online',
             * }
             */
            foreach ($dataArray['flags'] as &$item) {
                $item = $DB->quote($item);
            }

            $sql[] = <<<EOL
INSERT INTO
    `Encounters`.`UserFlags`
SET
    `user_id`   = {$userId},
    `is_vip`    = {$dataArray['flags']['is_vip']},
    `is_real`   = {$dataArray['flags']['is_real']},
    `is_leader` = {$dataArray['flags']['is_leader']},
    `maketop`   = {$dataArray['flags']['maketop']},
    `is_online` = {$dataArray['flags']['is_online']}
ON DUPLICATE KEY UPDATE
    `is_vip`    = {$dataArray['flags']['is_vip']},
    `is_real`   = {$dataArray['flags']['is_real']},
    `is_leader` = {$dataArray['flags']['is_leader']},
    `maketop`   = {$dataArray['flags']['maketop']},
    `is_online` = {$dataArray['flags']['is_online']}
;
EOL;

            /**
             * Type = {
             *     'height',
             *     'weight',
             *     'circumstance',
             *     'constitution',
             *     'smoke',
             *     'drink',
             *     'home',
             *     'language',
             *     'race',
             * }
             */
            foreach ($dataArray['type'] as &$item) {
                $item = $DB->quote($item);
            }

            $dataArray['type']['language'] = json_encode($dataArray['type']['language']);

            $sql[] = <<<EOL
INSERT INTO
    `Encounters`.`UserType`
SET
    `user_id`      = {$userId},
    `height`       = {$dataArray['type']['height']},
    `weight`       = {$dataArray['type']['weight']},
    `circumstance` = {$dataArray['type']['circumstance']},
    `constitution` = {$dataArray['type']['constitution']},
    `smoke`        = {$dataArray['type']['smoke']},
    `drink`        = {$dataArray['type']['drink']},
    `home`         = {$dataArray['type']['home']},
    `language`     = {$dataArray['type']['language']},
    `race`         = {$dataArray['type']['race']}
ON DUPLICATE KEY UPDATE
    `height`       = {$dataArray['type']['height']},
    `weight`       = {$dataArray['type']['weight']},
    `circumstance` = {$dataArray['type']['circumstance']},
    `constitution` = {$dataArray['type']['constitution']},
    `smoke`        = {$dataArray['type']['smoke']},
    `drink`        = {$dataArray['type']['drink']},
    `home`         = {$dataArray['type']['home']},
    `language`     = {$dataArray['type']['language']},
    `race`         = {$dataArray['type']['race']}
;
EOL;


            /**
             * Familiarity = {
             *     'lookfor',
             *     'waitingfor',
             *     'targets',
             *     'marital',
             *     'children',
             * }
             */
            $dataArray['familiarity']['targets'] = json_encode($dataArray['familiarity']['targets']);
            foreach ($dataArray['familiarity'] as &$item) {
                $item = $DB->quote($item);
            }

            $sql[] = <<<EOL
INSERT INTO
    `Encounters`.`UserFamiliarity`
SET
    `user_id`    = {$userId},
    `lookfor`    = {$dataArray['familiarity']['lookfor']},
    `waitingfor` = {$dataArray['familiarity']['waitingfor']},
    `targets`    = {$dataArray['familiarity']['targets']},
    `marital`    = {$dataArray['familiarity']['marital']},
    `children`   = {$dataArray['familiarity']['children']}
ON DUPLICATE KEY UPDATE
    `lookfor`    = {$dataArray['familiarity']['lookfor']},
    `waitingfor` = {$dataArray['familiarity']['waitingfor']},
    `targets`    = {$dataArray['familiarity']['targets']},
    `marital`    = {$dataArray['familiarity']['marital']},
    `children`   = {$dataArray['familiarity']['children']}
;
EOL;

            /**
             * Albums = {
             *     'albums',
             * }
             */
            $dataArray['albums'] = $DB->quote(json_encode($dataArray['albums']));

            $sql[] = <<<EOL
INSERT INTO
    `Encounters`.`UserAlbums`
SET
    `user_id` = {$userId},
    `albums`  = {$dataArray['albums']}
ON DUPLICATE KEY UPDATE
    `albums`  = {$dataArray['albums']}
;
EOL;

            /**
             * Photos = {
             *     'photos',
             * }
             */
            $dataArray['photos'] = $DB->quote(json_encode($dataArray['photos']));

            $sql[] = <<<EOL
INSERT INTO
    `Encounters`.`UserPhotos`
SET
    `user_id` = {$userId},
    `photos`  = {$dataArray['photos']}
ON DUPLICATE KEY UPDATE
    `photos`  = {$dataArray['photos']}
;
EOL;
            /**
             * Orientation = {
             *     'orientation'
             * }
             */
            foreach ($dataArray['orientation'] as &$item) {
                $item = $DB->quote($item);
            }

            $sql[] = <<<EOL
INSERT INTO
    `Encounters`.`UserOrientation`
SET
    `user_id`     = {$userId},
    `orientation` = {$dataArray['orientation']}
ON DUPLICATE KEY UPDATE
    `orientation` = {$dataArray['orientation']}
;
EOL;

        }

        $sql[] = "COMMIT;";

        $sql = implode("\n", $sql);

        $result = $this->getEntityManager()->getConnection()->exec($sql);
    }
}