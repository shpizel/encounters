<?php
namespace Mamba\EncountersBundle\Command;

use Core\ScriptBundle\CronScriptException;
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
        $MySQL = $this->getMySQL();

        $workload = unserialize($job->workload());
        $users = array_map(function($el){return(int)$el;}, $workload['users']);

        if (!count($users)) {
            return;
        }

        $this->log("Got task for <info>" . count($users) . "</info> users");

        $usersData = $this->getUsersHelper()->setApiRetryCount(5)->setSkipDatabase(true)->getInfo($users);

        $notExistsUsers = $users;
        foreach ($notExistsUsers as $userId) {
            if (isset($usersData[$userId])) {
                unset($notExistsUsers[array_search($userId, $notExistsUsers)]);
            }
        }

        if ($notExistsUsers) {

            /**
             * Работа с несуществующими юзерами
             *
             * @author shpizel
             */

            $sql = [
                "SET autocommit = 0;",
                "START TRANSACTION;",
            ];

            foreach ($notExistsUsers as $userId) {
                try {
                    if (!$this->getMamba()->nocache()->Anketa()->getInfo($userId)) {

                        /** закешируем */
                        $this->getMemcache()->set(
                            "user_{$userId}_info",
                            json_encode(
                                [
                                    'exists' => 0,
                                    'expires' => time() + Users::USER_INFO_LIFETIME
                                ]
                            )
                        );

                        $sql[] =
<<<EOL
INSERT INTO
    `Encounters`.`UserExists`
SET
    `user_id` = {$userId},
    `exists`  = 0
ON DUPLICATE KEY UPDATE
    `exists`  = 0
;
EOL;
                    }
                } catch (\Exception $e) {
                    //pass
                }
            }

            $sql[] = "COMMIT;";

            if (count($sql) > 3) {
                $sql = implode(PHP_EOL, $sql);
                $MySQL->exec($sql);
            }
        }

        if ($usersData) {
            $sql = [
                "SET autocommit = 0;",
                "START TRANSACTION;",
            ];

            /** закешируем информацию */
            foreach ($usersData as $userId => $userData) {

                /**
                 * @todo: использовать multiset
                 */
                $this->getMemcache()->set(
                    "user_{$userId}_info",
                    json_encode(
                        array_merge(
                            $userData,
                            [
                                'expires' => time() + Users::USER_INFO_LIFETIME
                            ]
                        )
                    )
                );
            }



            foreach ($usersData as $userId=>$dataArray) {

                $sql[] =
<<<EOL
INSERT INTO
    `Encounters`.`UserExists`
SET
    `user_id` = {$userId},
    `exists`  = 1
ON DUPLICATE KEY UPDATE
    `exists`  = 1
;
EOL;


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
                    $item = $MySQL->quote($item);
                }

                $sql[] =
<<<EOL
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
                    $item = $MySQL->quote($item);
                }

                $sql[] =
<<<EOL
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
                    $item = $MySQL->quote($item);
                }

                foreach ($dataArray['location']['region'] as &$item) {
                    $item = $MySQL->quote($item);
                }

                foreach ($dataArray['location']['city'] as &$item) {
                    $item = $MySQL->quote($item);
                }

                $sql[] =
<<<EOL
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
                $interestsCount = count($dataArray['interests']);
                $dataArray['interests'] = $MySQL->quote(json_encode($dataArray['interests'], JSON_PRETTY_PRINT));

                $sql[] =
<<<EOL
INSERT INTO
    `Encounters`.`UserInterests`
SET
    `user_id`   = {$userId},
    `interests` = {$dataArray['interests']},
    `count`     = {$interestsCount}
ON DUPLICATE KEY UPDATE
    `interests` = {$dataArray['interests']},
    `count`     = {$interestsCount}
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
                    $item = $MySQL->quote($item);
                }

                $sql[] =
<<<EOL
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
                    $item = $MySQL->quote($item);
                }

                $dataArray['type']['language'] = json_encode($dataArray['type']['language'], JSON_PRETTY_PRINT);

                $sql[] =
<<<EOL
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
                $dataArray['familiarity']['targets'] = json_encode($dataArray['familiarity']['targets'], JSON_PRETTY_PRINT);
                foreach ($dataArray['familiarity'] as &$item) {
                    $item = $MySQL->quote($item);
                }

                $sql[] =
<<<EOL
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
                $albumsCount = count($dataArray['albums']);
                $dataArray['albums'] = $MySQL->quote(json_encode($dataArray['albums'], JSON_PRETTY_PRINT));

                $sql[] =
<<<EOL
INSERT INTO
    `Encounters`.`UserAlbums`
SET
    `user_id` = {$userId},
    `albums`  = {$dataArray['albums']},
    `count`   = {$albumsCount}
ON DUPLICATE KEY UPDATE
    `albums`  = {$dataArray['albums']},
    `count`   = {$albumsCount}
;
EOL;

                /**
                 * Photos = {
                 *     'photos',
                 * }
                 */
                $photosCount = $dataArray['photos'];
                $dataArray['photos'] = $MySQL->quote(json_encode($dataArray['photos'], JSON_PRETTY_PRINT));

                $sql[] =
<<<EOL
INSERT INTO
    `Encounters`.`UserPhotos`
SET
    `user_id` = {$userId},
    `photos`  = {$dataArray['photos']},
    `count`   = {$photosCount}
ON DUPLICATE KEY UPDATE
    `photos`  = {$dataArray['photos']},
    `count`   = {$photosCount}
;
EOL;
                /**
                 * Orientation = {
                 *     'orientation'
                 * }
                 */
                foreach ($dataArray['orientation'] as &$item) {
                    $item = $MySQL->quote($item);
                }

                $sql[] =
<<<EOL
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
            $result = $MySQL->exec($sql);
        } else {
            return;
            throw new CronScriptException("Could not get user info");
        }
    }
}
