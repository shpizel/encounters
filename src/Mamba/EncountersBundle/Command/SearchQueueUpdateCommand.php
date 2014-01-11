<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\CronScript;

use Mamba\EncountersBundle\EncountersBundle;

use Mamba\EncountersBundle\Command\HitlistQueueUpdateCommand;
use Mamba\EncountersBundle\Command\ContactsQueueUpdateCommand;
use Mamba\EncountersBundle\Command\CurrentQueueUpdateCommand;

use PDO;

/**
 * SearchQueueUpdateCommand
 *
 * @package EncountersBundle
 */
class SearchQueueUpdateCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Search queue update",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:queue:search:update",

        SQL_PREPARE_WEB_USER_VARIABLES = "
            set @web_user_id := %d;
            select @web_user_age := `age` from UserInfo where user_id = @web_user_id;
            select @web_user_gender := `gender` from UserInfo where user_id = @web_user_id;
            select @web_user_orientation := `orientation` from UserOrientation where user_id = @web_user_id;
            select @web_user_country_id := `country_id` from UserLocation where user_id = @web_user_id;
            select @web_user_region_id := `region_id` from UserLocation where user_id = @web_user_id;
            select @web_user_city_id := `city_id` from UserLocation where user_id = @web_user_id;
            select @web_user_preferences_age_from := `age_from` from UserSearchPreferences where user_id = @web_user_id;
            select @web_user_preferences_age_to := `age_to` from UserSearchPreferences where user_id = @web_user_id;
            select @web_user_preferences_gender:= `gender` from UserSearchPreferences where user_id = @web_user_id;
        ",

        FULL_SEARCH_SQL = "
            SELECT
                info.user_id as user_id,
                energy.energy as energy
            FROM
                UserInfo info
            LEFT JOIN
                UserLocation location
            ON
                location.user_id = info.user_id
            LEFT JOIN
                UserOrientation orientation
            ON
                orientation.user_id = info.user_id
            LEFT JOIN
                UserPhotos photos
            ON
                photos.user_id = info.user_id
            LEFT JOIN
                UserEnergy energy
            ON
                energy.user_id = info.user_id
            LEFT JOIN
                UserSearchPreferences preferences
            ON
                preferences.user_id = info.user_id
            LEFT JOIN
                UserAvatar avatar
            ON
                avatar.user_id = info.user_id
            LEFT JOIN
                Decisions decisions
            ON
                (decisions.web_user_id = @web_user_id AND decisions.current_user_id = info.user_id)
            WHERE
                info.is_app_user = 1 AND
                info.gender = @web_user_preferences_gender AND
                location.country_id = @web_user_country_id AND
                location.region_id = @web_user_region_id AND
                (@web_user_city_id IS NULL OR location.city_id = @web_user_city_id) AND
                (info.age = 0 OR (info.age >= @web_user_preferences_age_from AND info.age <= @web_user_preferences_age_to)) AND
                decision IS NULL AND
                (photos.count != 0) AND
                orientation.orientation = @web_user_orientation AND
                (preferences.age_from IS NULL or @web_user_age IS NULL or @web_user_age >= preferences.age_from) AND
                (preferences.age_to IS NULL or @web_user_age IS NULL or @web_user_age <= preferences.age_to) AND
                (preferences.gender IS NULL or @web_user_gender = preferences.gender)
            ORDER BY
                energy DESC
            LIMIT
                256
        ",

        COUNTRY_AND_REGION_SEARCH_SQL = "
            SELECT
                info.user_id as user_id,
                energy.energy as energy
            FROM
                UserInfo info
            LEFT JOIN
                UserLocation location
            ON
                location.user_id = info.user_id
            LEFT JOIN
                UserOrientation orientation
            ON
                orientation.user_id = info.user_id
            LEFT JOIN
                UserPhotos photos
            ON
                photos.user_id = info.user_id
            LEFT JOIN
                UserEnergy energy
            ON
                energy.user_id = info.user_id
            LEFT JOIN
                UserSearchPreferences preferences
            ON
                preferences.user_id = info.user_id
            LEFT JOIN
                UserAvatar avatar
            ON
                avatar.user_id = info.user_id
            LEFT JOIN
                Decisions decisions
            ON
                (decisions.web_user_id = @web_user_id AND decisions.current_user_id = info.user_id)
            WHERE
                info.is_app_user = 1 AND
                info.gender = @web_user_preferences_gender AND
                location.country_id = @web_user_country_id AND
                location.region_id = @web_user_region_id AND
                (info.age = 0 OR (info.age >= @web_user_preferences_age_from AND info.age <= @web_user_preferences_age_to)) AND
                decision IS NULL AND
                (photos.count != 0) AND
                orientation.orientation = @web_user_orientation AND
                (preferences.age_from IS NULL or @web_user_age IS NULL or @web_user_age >= preferences.age_from) AND
                (preferences.age_to IS NULL or @web_user_age IS NULL or @web_user_age <= preferences.age_to) AND
                (preferences.gender IS NULL or @web_user_gender = preferences.gender)
            ORDER BY
                energy DESC
            LIMIT
                256;
        ",

        COUNTRY_SEARCH_SQL = "
            SELECT
                info.user_id as user_id,
                energy.energy as energy
            FROM
                UserInfo info
            LEFT JOIN
                UserLocation location
            ON
                location.user_id = info.user_id
            LEFT JOIN
                UserOrientation orientation
            ON
                orientation.user_id = info.user_id
            LEFT JOIN
                UserPhotos photos
            ON
                photos.user_id = info.user_id
            LEFT JOIN
                UserEnergy energy
            ON
                energy.user_id = info.user_id
            LEFT JOIN
                UserSearchPreferences preferences
            ON
                preferences.user_id = info.user_id
            LEFT JOIN
                UserAvatar avatar
            ON
                avatar.user_id = info.user_id
            LEFT JOIN
                Decisions decisions
            ON
                (decisions.web_user_id = @web_user_id AND decisions.current_user_id = info.user_id)
            WHERE
                info.is_app_user = 1 AND
                info.gender = @web_user_preferences_gender AND
                location.country_id = @web_user_country_id AND
                (info.age = 0 OR (info.age >= @web_user_preferences_age_from AND info.age <= @web_user_preferences_age_to)) AND
                decision IS NULL AND
                (photos.count != 0) AND
                orientation.orientation = @web_user_orientation AND
                (preferences.age_from IS NULL or @web_user_age IS NULL or @web_user_age >= preferences.age_from) AND
                (preferences.age_to IS NULL or @web_user_age IS NULL or @web_user_age <= preferences.age_to) AND
                (preferences.gender IS NULL or @web_user_gender = preferences.gender)
            ORDER BY
                energy DESC
            LIMIT
                256;
        ",

        /**
         * Лимит
         *
         * @var int
         */
        LIMIT = 32
    ;

    protected

        /**
         * Current user id
         *
         * @var int
         */
        $currentUserId
    ;

    /**
     * Лочит обработку очереди для этого юзера
     *
     * @return bool
     */
    public function lock() {
        return $this->getMemcache()->add($this->getLockName(), 1, ($this->daemon) ? 3600 : 60);
    }

    /**
     * Разлочивает обработку очереди для этого юзера
     *
     * @return bool
     */
    public function unlock() {
        return $this->getMemcache()->delete($this->getLockName());
    }

    /**
     * Возвращает имя лока
     *
     * @var str
     */
    public function getLockName() {
        return md5(self::SCRIPT_NAME . "_lock_by_" . $this->currentUserId);
    }

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $worker = $this->getGearmanWorker();

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_SEARCH_QUEUE_UPDATE_FUNCTION_NAME, function($job) use($class) {
            try {
                return $class->updateSearchQueue($job);
            } catch (\Exception $e) {
                $class->log("Error: " . static::SCRIPT_NAME . ":" . $e->getCode() . " " . $e->getMessage(), 16);
                $class->unlock();

                return;
            }
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
                $this->unlock();

                break;
            } elseif ($worker->returnCode() == GEARMAN_SUCCESS) {
                $this->log("Completed", 64);
            }

            $this->unlock();
        }

        $this->log("Bye", 48);
    }

    /**
     * Обновляет пользовательскую очередь из поиска
     *
     * @param $job
     */
    public function updateSearchQueue($job) {
        $Mamba = $this->getMamba();
        list($webUserId, $timestamp) = array_values(unserialize($job->workload()));

        $this->log("Got task for user_id = $webUserId", 64);

        $this->currentUserId = $webUserId;
        if (!$this->lock()) {
            throw new \Core\ScriptBundle\CronScriptException("Could not obtain lock");
        }

        if ($webUserId = (int) $webUserId) {
            $Mamba->set('oid', $webUserId);

            if (!$Mamba->getReady()) {
                throw new \Core\ScriptBundle\CronScriptException("Mamba is not ready!");
            }
        } else {
            throw new \Core\ScriptBundle\CronScriptException("Invalid workload");
        }

        if (!($searchPreferences = $this->getSearchPreferencesHelper()->get($webUserId))) {
            throw new \Core\ScriptBundle\CronScriptException("Could not get search preferences for user_id=$webUserId");
        }

        if ($searchPreferences['changed'] > $timestamp) {
            return;
        }

        if ($this->getSearchQueueHelper()->getSize($webUserId) >= self::LIMIT) {
            throw new \Core\ScriptBundle\CronScriptException("Search queue for user_id=$webUserId has limit exceed");
        }

        $usersAddedCount = 0;

        $this->getMySQL()->exec(sprintf(self::SQL_PREPARE_WEB_USER_VARIABLES, $webUserId));

        $this->log("Searching by DB with country, region and city..", 64);

        $Query = $this->getMySQL()->getQuery(self::FULL_SEARCH_SQL)->bindArray([
            ['gender', $searchPreferences['gender']],
            ['orientation', $searchPreferences['orientation']],
            ['age_from', $searchPreferences['age_from']],
            ['age_to', $searchPreferences['age_to']],
            ['country_id', $searchPreferences['geo']['country_id']],
            ['region_id', $searchPreferences['geo']['region_id']],
            ['city_id', $searchPreferences['geo']['city_id']],
            ['web_user_id', $webUserId],
        ]);

        if ($result = $Query->execute()->getResult()) {
            $this->log("SQL query OK", 64);

            while ($item = $Query->fetch()) {
                $currentUserId = (int) $item['user_id'];

                if (!$this->getViewedQueueHelper()->exists($webUserId, $currentUserId) &&
                    !$this->getCurrentQueueHelper()->exists($webUserId, $currentUserId) &&
                    !$this->getMemcache()->get("invalid_photos_by_{$currentUserId}")
                ) {
                    if ($this->getSearchQueueHelper()->put($webUserId, $currentUserId, $this->getEnergyHelper()->get($currentUserId))) {
                        $usersAddedCount++;
                    }

                    if ($usersAddedCount >= self::LIMIT) {
                        break;
                    }
                }
            }

            $this->log("<error>$usersAddedCount</error> users were added to search queue for <info>user_id</info> = {$webUserId}");
        } else {
            $this->log("SQL query FAILED", 64);
        }

        if ($usersAddedCount < self::LIMIT) {
            $this->log("Searching by DB with country and region..", 64);

            $Query = $this->getMySQL()->getQuery(self::COUNTRY_AND_REGION_SEARCH_SQL)->bindArray([
                ['gender', $searchPreferences['gender']],
                ['orientation', $searchPreferences['orientation']],
                ['age_from', $searchPreferences['age_from']],
                ['age_to', $searchPreferences['age_to']],
                ['country_id', $searchPreferences['geo']['country_id']],
                ['region_id', $searchPreferences['geo']['region_id']],
                ['web_user_id', $webUserId],
            ]);

            if ($result = $Query->execute()->getResult()) {
                $this->log("SQL query OK", 64);

                while ($item = $Query->fetch(PDO::FETCH_ASSOC)) {
                    $currentUserId = (int) $item['user_id'];
                    if (!$this->getViewedQueueHelper()->exists($webUserId, $currentUserId) &&
                        !$this->getCurrentQueueHelper()->exists($webUserId, $currentUserId) &&
                        !$this->getMemcache()->get("invalid_photos_by_{$currentUserId}")
                    ) {
                        if ($this->getSearchQueueHelper()->put($webUserId, $currentUserId, $this->getEnergyHelper()->get($currentUserId))) {
                            $usersAddedCount++;
                        }

                        if ($usersAddedCount >= self::LIMIT) {
                            break;
                        }
                    }
                }

                $this->log("[Search queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added;");
            } else {
                $this->log("SQL query FAILED", 16);
            }
        }

        if ($usersAddedCount < self::LIMIT) {
            $this->log("Searching by DB with country..", 64);

            $Query = $this->getMySQL()->getQuery(self::COUNTRY_SEARCH_SQL)->bindArray([
                ['gender', $searchPreferences['gender']],
                ['orientation', $searchPreferences['orientation']],
                ['age_from', $searchPreferences['age_from']],
                ['age_to', $searchPreferences['age_to']],
                ['country_id', $searchPreferences['geo']['country_id']],
                ['web_user_id', $webUserId],
            ]);

            if ($result = $Query->execute()->getResult()) {
                $this->log("SQL query OK", 64);

                while ($item = $Query->fetch(PDO::FETCH_ASSOC)) {
                    $currentUserId = (int) $item['user_id'];
                    if (!$this->getViewedQueueHelper()->exists($webUserId, $currentUserId) &&
                        !$this->getCurrentQueueHelper()->exists($webUserId, $currentUserId) &&
                        !$this->getMemcache()->get("invalid_photos_by_{$currentUserId}")
                    ) {
                        if ($this->getSearchQueueHelper()->put($webUserId, $currentUserId, $this->getEnergyHelper()->get($currentUserId))) {
                            $usersAddedCount++;
                        }

                        if ($usersAddedCount >= self::LIMIT) {
                            break;
                        }
                    }
                }

                $this->log("[Search queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added;");
            } else {
                $this->log("SQL query FAILED", 16);
            }
        }

        if ($usersAddedCount < self::LIMIT) {
            $this->log("Searching by ASearch with country, region, city..", 64);

            $offset = $this->getMemcache()->get("asearch_page_by_{$webUserId}_{$searchPreferences['geo']['country_id']}_{$searchPreferences['geo']['region_id']}_{$searchPreferences['geo']['city_id']}")
                ?: -10;

            do {
                $Mamba->multi();

                foreach (range(1, 16) as $i) {
                    $Mamba->Search()->get(
                        $whoAmI         = null,
                        $lookingFor     = $searchPreferences['gender'],
                        $ageFrom        = (int) $searchPreferences['age_from'],
                        $ageTo          = (int) $searchPreferences['age_to'],
//                        $target         = null,
                        $onlyWithPhoto  = true,
                        $onlyReal       = true,
                        $onlyWithWebCam = false,
//                        $noIntim        = true,
                        $countryId      = (int) $searchPreferences['geo']['country_id'],
                        $regionId       = (int) $searchPreferences['geo']['region_id'],
                        $cityId         = (int) $searchPreferences['geo']['city_id'],
                        $metroId        = null,
                        $offset         = $offset + 10,
                        $blocks         = array(),
                        $idsOnly        = true
                    );
                }

                if ($results = $Mamba->exec()) {
                    foreach ($results as $rindex => $result) {
                        if (isset($result['users'])) {

                            /** Запишем номер страницы которую мы смотрим */
                            $this->log("Offset: " . ($offset - (16 - $rindex)*10), 48);
                            $this->getMemcache()->set(
                                "asearch_page_by_{$webUserId}_{$searchPreferences['geo']['country_id']}_{$searchPreferences['geo']['region_id']}_{$searchPreferences['geo']['city_id']}",
                                $offset - (16 - $rindex)*10,
                                12*3600
                            );

                            foreach ($result['users'] as $currentUserId) {
                                if (is_int($currentUserId) && !$this->getSearchPreferencesHelper()->exists($currentUserId) && !$this->getViewedQueueHelper()->exists($webUserId, $currentUserId) && !$this->getCurrentQueueHelper()->exists($webUserId, $currentUserId)) {
                                    $this->getSearchQueueHelper()->put($webUserId, $currentUserId, $this->getEnergyHelper()->get($currentUserId))
                                    && $usersAddedCount++;

                                    if ($usersAddedCount >= self::LIMIT) {
                                        break;
                                    }
                                }
                            }
                        }
                    }

                    $this->log("[Search queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added at offset <comment>$offset</comment>");

                    if (!(isset($result['users']) && count($result['users']) && $usersAddedCount < self::LIMIT)) {
                        break;
                    }
                }
            }
            while (true);
        }

        if ($usersAddedCount < self::LIMIT) {
            $this->log("Searching by ASearch with country and region..", 64);

            $offset = $this->getMemcache()->get("asearch_page_by_{$webUserId}_{$searchPreferences['geo']['country_id']}_{$searchPreferences['geo']['region_id']}")
                ?: -10;

            do {
                $Mamba->multi();

                foreach (range(1, 16) as $i) {
                    $Mamba->Search()->get(
                        $whoAmI         = null,
                        $lookingFor     = $searchPreferences['gender'],
                        $ageFrom        = (int) $searchPreferences['age_from'],
                        $ageTo          = (int) $searchPreferences['age_to'],
//                        $target         = null,
                        $onlyWithPhoto  = true,
                        $onlyReal       = true,
                        $onlyWithWebCam = false,
//                        $noIntim        = true,
                        $countryId      = (int) $searchPreferences['geo']['country_id'],
                        $regionId       = (int) $searchPreferences['geo']['region_id'],
                        $cityId         = null,
                        $metroId        = null,
                        $offset         = $offset + 10,
                        $blocks         = array(),
                        $idsOnly        = true
                    );
                }

                if ($results = $Mamba->exec()) {
                    foreach ($results as $rindex=>$result) {

                        /** Запишем номер страницы которую мы смотрим */
                        $this->log("Offset: " . ($offset - (16 - $rindex)*10), 48);
                        $this->getMemcache()->set(
                            "asearch_page_by_{$webUserId}_{$searchPreferences['geo']['country_id']}_{$searchPreferences['geo']['region_id']}",
                            $offset - (16 - $rindex)*10,
                            12*3600
                        );

                        if (isset($result['users'])) {
                            foreach ($result['users'] as $currentUserId) {
                                if (is_int($currentUserId) && !$this->getSearchPreferencesHelper()->exists($currentUserId) && !$this->getViewedQueueHelper()->exists($webUserId, $currentUserId) && !$this->getCurrentQueueHelper()->exists($webUserId, $currentUserId)) {
                                    $this->getSearchQueueHelper()->put($webUserId, $currentUserId, $this->getEnergyHelper()->get($currentUserId))
                                    && $usersAddedCount++;

                                    if ($usersAddedCount >= self::LIMIT) {
                                        break;
                                    }
                                }
                            }
                        }
                    }

                    $this->log("[Search queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added at offset <comment>$offset</comment>");

                    if (!(isset($result['users']) && count($result['users']) && $usersAddedCount < self::LIMIT)) {
                        break;
                    }
                }
            }
            while (true);
        }

        if ($usersAddedCount < self::LIMIT) {
            $this->log("Searching by ASearch with country..", 64);

            $offset = $this->getMemcache()->get("asearch_page_by_{$webUserId}_{$searchPreferences['geo']['country_id']}")
                ?: -10;

            do {
                $Mamba->multi();

                foreach (range(1, 16) as $i) {
                    $Mamba->Search()->get(
                        $whoAmI         = null,
                        $lookingFor     = $searchPreferences['gender'],
                        $ageFrom        = (int) $searchPreferences['age_from'],
                        $ageTo          = (int) $searchPreferences['age_to'],
//                        $target         = null,
                        $onlyWithPhoto  = true,
                        $onlyReal       = true,
                        $onlyWithWebCam = false,
//                        $noIntim        = true,
                        $countryId      = (int) $searchPreferences['geo']['country_id'],
                        $regionId       = null,
                        $cityId         = null,
                        $metroId        = null,
                        $offset         = $offset + 10,
                        $blocks         = array(),
                        $idsOnly        = true
                    );
                }

                if ($results = $Mamba->exec()) {
                    foreach ($results as $rindex=>$result) {

                        /** Запишем номер страницы которую мы смотрим */
                        $this->log("Offset: " . ($offset - (16 - $rindex)*10), 48);
                        $this->getMemcache()->set(
                            "asearch_page_by_{$webUserId}_{$searchPreferences['geo']['country_id']}",
                            $offset - (16 - $rindex)*10,
                            12*3600
                        );

                        if (isset($result['users'])) {
                            foreach ($result['users'] as $currentUserId) {
                                if (is_int($currentUserId) && !$this->getSearchPreferencesHelper()->exists($currentUserId) && !$this->getViewedQueueHelper()->exists($webUserId, $currentUserId) && !$this->getCurrentQueueHelper()->exists($webUserId, $currentUserId)) {
                                    $this->getSearchQueueHelper()->put($webUserId, $currentUserId, $this->getEnergyHelper()->get($currentUserId))
                                        && $usersAddedCount++;

                                    if ($usersAddedCount >= self::LIMIT) {
                                        break;
                                    }
                                }
                            }
                        }
                    }

                    $this->log("[Search queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added at offset <comment>$offset</comment>");

                    if (!(isset($result['users']) && count($result['users']) && $usersAddedCount < self::LIMIT)) {
                        break;
                    }
                }
            }
            while (true);
        }

        if ($usersAddedCount < self::LIMIT) {
            $this->log("Searching by ASearch with RUSSIA..", 64);

            $offset = -10;
            do {
                $Mamba->multi();

                foreach (range(1, 16) as $i) {
                    $Mamba->Search()->get(
                        $whoAmI         = null,
                        $lookingFor     = $searchPreferences['gender'],
                        $ageFrom        = (int) $searchPreferences['age_from'],
                        $ageTo          = (int) $searchPreferences['age_to'],
//                        $target         = null,
                        $onlyWithPhoto  = true,
                        $onlyReal       = true,
                        $onlyWithWebCam = false,
//                        $noIntim        = true,
                        $countryId      = 3159,
                        $regionId       = null,
                        $cityId         = null,
                        $metroId        = null,
                        $offset         = $offset + 10,
                        $blocks         = array(),
                        $idsOnly        = true
                    );
                }

                if ($results = $Mamba->exec()) {
                    foreach ($results as $result) {
                        if (isset($result['users'])) {
                            foreach ($result['users'] as $currentUserId) {
                                if (is_int($currentUserId) && !$this->getSearchPreferencesHelper()->exists($currentUserId) && !$this->getViewedQueueHelper()->exists($webUserId, $currentUserId) && !$this->getCurrentQueueHelper()->exists($webUserId, $currentUserId)) {
                                    $this->getSearchQueueHelper()->put($webUserId, $currentUserId, $this->getEnergyHelper()->get($currentUserId))
                                        && $usersAddedCount++;

                                    if ($usersAddedCount >= self::LIMIT) {
                                        break;
                                    }
                                }
                            }
                        }
                    }

                    $this->log("[Search queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added at offset <comment>$offset</comment>");

                    if (!(isset($result['users']) && count($result['users']) && $usersAddedCount < self::LIMIT)) {
                        break;
                    }
                }
            }
            while (true);
        }
    }
}