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

        FULL_SEARCH_SQL = "
            SELECT
                u.user_id, e.energy
            FROM
                Encounters.User u
            INNER JOIN
                Encounters.Energy e
            ON
                e.user_id = u.user_id
            WHERE
                u.gender = :gender AND
                u.orientation = :orientation AND
                e.energy > 0 AND
                (u.age = 0 OR (u.age >= :age_from AND u.age <= :age_to)) AND

                u.country_id = :country_id AND
                u.region_id = :region_id AND
                u.city_id = :city_id AND
                u.user_id NOT IN (
                    SELECT
                        current_user_id
                    FROM
                        Decisions
                    WHERE
                        web_user_id = :web_user_id
                )

            ORDER BY
                e.energy DESC
            LIMIT
                2048
        ",

        COUNTRY_AND_REGION_SEARCH_SQL = "
            SELECT
                u.user_id, e.energy
            FROM
                Encounters.User u
            INNER JOIN
                Encounters.Energy e
            ON
                e.user_id = u.user_id
            WHERE
                u.gender = :gender AND
                u.orientation = :orientation AND
                e.energy > 0 AND
                (u.age = 0 OR (u.age >= :age_from AND u.age <= :age_to)) AND

                u.country_id = :country_id AND
                u.region_id = :region_id AND
                u.user_id NOT IN (
                    SELECT
                        current_user_id
                    FROM
                        Decisions
                    WHERE
                        web_user_id = :web_user_id
                )
            ORDER BY
                e.energy DESC
            LIMIT
                2048
        ",

        COUNTRY_SEARCH_SQL = "
            SELECT
                u.user_id, e.energy
            FROM
                Encounters.User u
            INNER JOIN
                Encounters.Energy e
            ON
                e.user_id = u.user_id
            WHERE
                u.gender = :gender AND
                u.orientation = :orientation AND
                e.energy > 0 AND
                (u.age = 0 OR (u.age >= :age_from AND u.age <= :age_to)) AND

                u.country_id = :country_id AND
                u.user_id NOT IN (
                    SELECT
                        current_user_id
                    FROM
                        Decisions
                    WHERE
                        web_user_id = :web_user_id
                )
            ORDER BY
                e.energy DESC
            LIMIT
                2048
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
                $class->log($e->getCode() . ": " . $e->getMessage(), 16);
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
        $_webUserId = $webUserId; //copy for PDO

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

        if (!($searchPreferences = $this->getSearchPreferencesObject()->get($webUserId))) {
            throw new \Core\ScriptBundle\CronScriptException("Could not get search preferences for user_id=$webUserId");
        }

        if ($searchPreferences['changed'] > $timestamp) {
            return;
        }

        if ($this->getSearchQueueObject()->getSize($webUserId) >= self::LIMIT) {
            throw new \Core\ScriptBundle\CronScriptException("Search queue for user_id=$webUserId has limit exceed");
        }

        $usersAddedCount = 0;

        $this->log("Searching by DB with country, region, city..", 64);
        $stmt = $this->getEntityManager()->getConnection()->prepare(self::FULL_SEARCH_SQL);

        $stmt->bindParam('gender', $searchPreferences['gender']);
        $stmt->bindParam('orientation', $searchPreferences['orientation']);
        $stmt->bindParam('age_from', $searchPreferences['age_from']);
        $stmt->bindParam('age_to', $searchPreferences['age_to']);
        $stmt->bindParam('country_id', $searchPreferences['geo']['country_id']);
        $stmt->bindParam('region_id', $searchPreferences['geo']['region_id']);
        $stmt->bindParam('city_id', $searchPreferences['geo']['city_id']);
        $stmt->bindParam('web_user_id', $_webUserId);

        if ($result = $stmt->execute()) {
            $this->log("SQL query OK", 64);

            while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $currentUserId = (int) $item['user_id'];

                if (!$this->getViewedQueueObject()->exists($webUserId, $currentUserId) &&
                    !$this->getCurrentQueueObject()->exists($webUserId, $currentUserId) &&
                    !$this->getMemcache()->get("invalid_photos_by_{$currentUserId}")
                ) {
                    try {
                        $this->getMamba()->Photos()->get($currentUserId);
                        $this->getSearchQueueObject()->put(
                            $webUserId,
                            $currentUserId,
                            $this->getEnergyObject()->get($currentUserId)
                        ) && $usersAddedCount++;

                        if ($usersAddedCount >= self::LIMIT) {
                            break;
                        }
                    } catch (\Exception $e) {
                        $this->getMemcache()->set("invalid_photos_by_{$currentUserId}", 1, 86400);
                    }
                }
            }

            $this->log("<error>$usersAddedCount</error> users were added to search queue for <info>user_id</info> = {$webUserId}");
        } else {
            $this->log("SQL query FAILED", 64);
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
                        $target         = null,
                        $onlyWithPhoto  = true,
                        $onlyReal       = true,
                        $onlyWithWebCam = false,
                        $noIntim        = true,
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
                                if (is_int($currentUserId) && !$this->getSearchPreferencesObject()->exists($currentUserId) && !$this->getViewedQueueObject()->exists($webUserId, $currentUserId) && !$this->getCurrentQueueObject()->exists($webUserId, $currentUserId)) {
                                    $this->getSearchQueueObject()->put($webUserId, $currentUserId, $this->getEnergyObject()->get($currentUserId))
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
            } while (true);
        }

        if ($usersAddedCount < self::LIMIT) {
            $this->log("Searching by DB with country and region..", 64);
            $stmt = $this->getEntityManager()->getConnection()->prepare(self::COUNTRY_AND_REGION_SEARCH_SQL);

            $stmt->bindParam('gender', $searchPreferences['gender']);
            $stmt->bindParam('orientation', $searchPreferences['orientation']);
            $stmt->bindParam('age_from', $searchPreferences['age_from']);
            $stmt->bindParam('age_to', $searchPreferences['age_to']);
            $stmt->bindParam('country_id', $searchPreferences['geo']['country_id']);
            $stmt->bindParam('region_id', $searchPreferences['geo']['region_id']);
            $stmt->bindParam('web_user_id', $_webUserId);

            if ($result = $stmt->execute()) {
                $this->log("SQL query OK", 64);

                while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $currentUserId = (int) $item['user_id'];
                    if (!$this->getViewedQueueObject()->exists($webUserId, $currentUserId) &&
                        !$this->getCurrentQueueObject()->exists($webUserId, $currentUserId) &&
                        !$this->getMemcache()->get("invalid_photos_by_{$currentUserId}")
                    ) {
                        try {
                            $this->getMamba()->Photos()->get($currentUserId);
                            $this->getSearchQueueObject()->put(
                                $webUserId,
                                $currentUserId,
                                $this->getEnergyObject()->get($currentUserId)
                            ) && $usersAddedCount++;

                            if ($usersAddedCount >= self::LIMIT) {
                                break;
                            }
                        } catch (\Exception $e) {
                            $this->getMemcache()->set("invalid_photos_by_{$currentUserId}", 1, 86400);
                        }
                    }
                }

                $this->log("[Search queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added;");
            } else {
                $this->log("SQL query FAILED", 16);
            }
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
                        $target         = null,
                        $onlyWithPhoto  = true,
                        $onlyReal       = true,
                        $onlyWithWebCam = false,
                        $noIntim        = true,
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
                                if (is_int($currentUserId) && !$this->getSearchPreferencesObject()->exists($currentUserId) && !$this->getViewedQueueObject()->exists($webUserId, $currentUserId) && !$this->getCurrentQueueObject()->exists($webUserId, $currentUserId)) {
                                    $this->getSearchQueueObject()->put($webUserId, $currentUserId, $this->getEnergyObject()->get($currentUserId))
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
            } while (true);
        }

        if ($usersAddedCount < self::LIMIT) {
            $this->log("Searching by DB with country..", 64);
            $stmt = $this->getEntityManager()->getConnection()->prepare(self::COUNTRY_SEARCH_SQL);

            $stmt->bindParam('gender', $searchPreferences['gender']);
            $stmt->bindParam('orientation', $searchPreferences['orientation']);
            $stmt->bindParam('age_from', $searchPreferences['age_from']);
            $stmt->bindParam('age_to', $searchPreferences['age_to']);
            $stmt->bindParam('country_id', $searchPreferences['geo']['country_id']);
            $stmt->bindParam('web_user_id', $_webUserId);

            if ($result = $stmt->execute()) {
                $this->log("SQL query OK", 64);

                while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $currentUserId = (int) $item['user_id'];
                    if (!$this->getViewedQueueObject()->exists($webUserId, $currentUserId) &&
                        !$this->getCurrentQueueObject()->exists($webUserId, $currentUserId) &&
                        !$this->getMemcache()->get("invalid_photos_by_{$currentUserId}")
                    ) {
                        try {
                            $this->getMamba()->Photos()->get($currentUserId);
                            $this->getSearchQueueObject()->put(
                                $webUserId,
                                $currentUserId,
                                $this->getEnergyObject()->get($currentUserId)
                            ) && $usersAddedCount++;

                            if ($usersAddedCount >= self::LIMIT) {
                                break;
                            }
                        } catch (\Exception $e) {
                            $this->getMemcache()->set("invalid_photos_by_{$currentUserId}", 1, 86400);
                        }
                    }
                }

                $this->log("[Search queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added;");
            } else {
                $this->log("SQL query FAILED", 16);
            }
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
                        $target         = null,
                        $onlyWithPhoto  = true,
                        $onlyReal       = true,
                        $onlyWithWebCam = false,
                        $noIntim        = true,
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
                                if (is_int($currentUserId) && !$this->getSearchPreferencesObject()->exists($currentUserId) && !$this->getViewedQueueObject()->exists($webUserId, $currentUserId) && !$this->getCurrentQueueObject()->exists($webUserId, $currentUserId)) {
                                    $this->getSearchQueueObject()->put($webUserId, $currentUserId, $this->getEnergyObject()->get($currentUserId))
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
            } while (true);
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
                        $target         = null,
                        $onlyWithPhoto  = true,
                        $onlyReal       = true,
                        $onlyWithWebCam = false,
                        $noIntim        = true,
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
                                if (is_int($currentUserId) && !$this->getSearchPreferencesObject()->exists($currentUserId) && !$this->getViewedQueueObject()->exists($webUserId, $currentUserId) && !$this->getCurrentQueueObject()->exists($webUserId, $currentUserId)) {
                                    $this->getSearchQueueObject()->put($webUserId, $currentUserId, $this->getEnergyObject()->get($currentUserId))
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
            } while (true);
        }
    }
}