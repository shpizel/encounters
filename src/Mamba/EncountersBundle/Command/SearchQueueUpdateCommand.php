<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\CronScript;
use Mamba\EncountersBundle\EncountersBundle;
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
                e.energy > 128 AND
                (u.age = 0 OR (u.age >= :age_from AND u.age <= :age_to)) AND

                u.country_id = :country_id AND
                u.region_id = :region_id AND
                u.city_id = :city_id
            ORDER BY
                e.energy DESC
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
                e.energy > 128 AND
                (u.age = 0 OR (u.age >= :age_from AND u.age <= :age_to)) AND

                u.country_id = :country_id AND
                u.region_id = :region_id
            ORDER BY
                e.energy DESC
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
                e.energy > 128 AND
                (u.age = 0 OR (u.age >= :age_from AND u.age <= :age_to)) AND

                u.country_id = :country_id
            ORDER BY
                e.energy DESC
        ",

        /**
         * Лимит
         *
         * @var int
         */
        LIMIT = 32
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $worker = $this->getGearman()->getWorker();
        $worker->setTimeout(static::GEARMAN_WORKER_TIMEOUT);

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_SEARCH_QUEUE_UPDATE_FUNCTION_NAME, function($job) use($class) {
            try {
                return $class->updateSearchQueue($job);
            } catch (\Exception $e) {
                $class->log($e->getCode() . ": " . $e->getMessage(), 16);
                return;
            }
        });

        while
        (
            !$this->getMemcache()->get("cron:stop") &&
            ((time() - $this->started < $this->lifetime) || !$this->lifetime) &&
            ((memory_get_usage() < $this->memory) || !$this->memory) &&
            --$this->iterations &&
            (@$worker->work() || $worker->returnCode() == GEARMAN_TIMEOUT)
        ) {
            $this->log("Iterations: {$this->iterations}", 64);
            if ($worker->returnCode() == GEARMAN_TIMEOUT) {
                $this->log("Timed out", 48);
                continue;
            } elseif ($worker->returnCode() != GEARMAN_SUCCESS) {
                $this->log("Failed", 16);
                break;
            } elseif ($worker->returnCode() == GEARMAN_SUCCESS) {
                $this->log("Success", 64);
            }
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
        $Redis = $this->getRedis();

        list($webUserId, $timestamp) = array_values(unserialize($job->workload()));
        if ($webUserId = (int) $webUserId) {
            $Mamba->set('oid', $webUserId);

            if (!$Mamba->getReady()) {
                $this->log("Mamba is not ready!", 16);
                return;
            }
        } else {
            throw new CronScriptException("Invalid workload");
        }

        if (!($searchPreferences = $this->getSearchPreferencesObject()->get($webUserId))) {
            throw new CronScriptException("Could not get search preferences for user_id=$webUserId");
        }

        if ($searchPreferences['changed'] > $timestamp) {
            return;
        }

        if ($this->getSearchQueueObject()->getSize($webUserId) >= self::LIMIT) {
            return;
        }

        $usersAddedCount = 0;

        /** Ищем по базе, полностью */
        $stmt = $this->getEntityManager()->getConnection()->prepare(self::FULL_SEARCH_SQL);

        $stmt->bindParam('gender', $searchPreferences['gender']);
        $stmt->bindParam('age_from', $searchPreferences['age_from']);
        $stmt->bindParam('age_to', $searchPreferences['age_to']);
        $stmt->bindParam('country_id', $searchPreferences['geo']['country_id']);
        $stmt->bindParam('region_id', $searchPreferences['geo']['region_id']);
        $stmt->bindParam('city_id', $searchPreferences['geo']['city_id']);

        if ($result = $stmt->execute()) {
            while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (!$this->getViewedQueueObject()->exists($webUserId, $currentUserId = (int) $item['user_id'])) {

                    try {
                        $this->getMamba()->Photos()->get($currentUserId);

                        $this->getSearchQueueObject()->put($webUserId, $currentUserId, $this->getEnergyObject()->get($currentUserId))
                            && $usersAddedCount++;

                        if ($usersAddedCount >= self::LIMIT) {
                            break;
                        }
                    } catch (\Exception $e) {

                    }
                }
            }

            $this->log("[Search queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added;");
        }

        if ($usersAddedCount < self::LIMIT) {
            $offset = -10;
            do {
                $result = $Mamba->Search()->get(
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

                if (isset($result['users'])) {
                    foreach ($result['users'] as $currentUserId) {
                        if (is_int($currentUserId) && !$this->getViewedQueueObject()->exists($webUserId, $currentUserId)) {
                            $this->getSearchQueueObject()->put($webUserId, $currentUserId, $this->getEnergyObject()->get($currentUserId))
                                && $usersAddedCount++;

                            if ($usersAddedCount >= self::LIMIT) {
                                break;
                            }
                        }
                    }
                }

                $this->log("[Search queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added;");
            } while (isset($result['users']) && count($result['users']) && $usersAddedCount < self::LIMIT);
        }

        if ($usersAddedCount < self::LIMIT) {

            /** Ищем по базе, страна и регион */
            $stmt = $this->getEntityManager()->getConnection()->prepare(self::COUNTRY_AND_REGION_SEARCH_SQL);

            $stmt->bindParam('gender', $searchPreferences['gender']);
            $stmt->bindParam('age_from', $searchPreferences['age_from']);
            $stmt->bindParam('age_to', $searchPreferences['age_to']);
            $stmt->bindParam('country_id', $searchPreferences['geo']['country_id']);
            $stmt->bindParam('region_id', $searchPreferences['geo']['region_id']);

            if ($result = $stmt->execute()) {
                while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if (!$this->getViewedQueueObject()->exists($webUserId, $currentUserId = (int) $item['user_id'])) {

                        try {
                            $this->getMamba()->Photos()->get($currentUserId);

                            $this->getSearchQueueObject()->put($webUserId, $currentUserId, $this->getEnergyObject()->get($currentUserId))
                                && $usersAddedCount++;

                            if ($usersAddedCount >= self::LIMIT) {
                                break;
                            }
                        } catch (\Exception $e) {

                        }
                    }
                }

                $this->log("[Search queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added;");
            }
        }

        /** Никого не нашли — ищем по стране и региону */
        if ($usersAddedCount < self::LIMIT) {

            $offset = -10;
            $usersAddedCount = 0;
            do {
                $result = $Mamba->Search()->get(
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

                if (isset($result['users'])) {
                    foreach ($result['users'] as $currentUserId) {
                        if (is_int($currentUserId) && !$this->getViewedQueueObject()->exists($webUserId, $currentUserId)) {
                            $this->getSearchQueueObject()->put($webUserId, $currentUserId, $this->getEnergyObject()->get($currentUserId))
                                && $usersAddedCount++;

                            if ($usersAddedCount >= self::LIMIT) {
                                break;
                            }
                        }
                    }
                }

                $this->log("[Search queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added;");
            } while (isset($result['users']) && count($result['users']) && $usersAddedCount < self::LIMIT);
        }

        if ($usersAddedCount < self::LIMIT) {

            /** Ищем по базе, страна */
            $stmt = $this->getEntityManager()->getConnection()->prepare(self::COUNTRY_SEARCH_SQL);

            $stmt->bindParam('gender', $searchPreferences['gender']);
            $stmt->bindParam('age_from', $searchPreferences['age_from']);
            $stmt->bindParam('age_to', $searchPreferences['age_to']);
            $stmt->bindParam('country_id', $searchPreferences['geo']['country_id']);

            if ($result = $stmt->execute()) {
                while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if (!$this->getViewedQueueObject()->exists($webUserId, $currentUserId = (int) $item['user_id'])) {

                        try {
                            $this->getMamba()->Photos()->get($currentUserId);

                            $this->getSearchQueueObject()->put($webUserId, $currentUserId, $this->getEnergyObject()->get($currentUserId))
                                && $usersAddedCount++;

                            if ($usersAddedCount >= self::LIMIT) {
                                break;
                            }
                        } catch (\Exception $e) {

                        }
                    }
                }

                $this->log("[Search queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added;");
            }
        }

        /** Никого не нашли — ищем по стране */
        if ($usersAddedCount < self::LIMIT) {

            $offset = -10;
            $usersAddedCount = 0;
            do {
                $result = $Mamba->Search()->get(
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

                if (isset($result['users'])) {
                    foreach ($result['users'] as $currentUserId) {
                        if (is_int($currentUserId) && !$this->getViewedQueueObject()->exists($webUserId, $currentUserId)) {
                            $this->getSearchQueueObject()->put($webUserId, $currentUserId, $this->getEnergyObject()->get($currentUserId))
                                && $usersAddedCount++;

                            if ($usersAddedCount >= self::LIMIT) {
                                break;
                            }
                        }
                    }
                }

                $this->log("[Search queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added;");
            } while (isset($result['users']) && count($result['users']) && $usersAddedCount < self::LIMIT);
        }

        /** Никого не нашли — ищем по Рашке */
        if ($usersAddedCount < self::LIMIT) {

            $offset = -10;
            $usersAddedCount = 0;
            do {
                $result = $Mamba->Search()->get(
                    $whoAmI         = null,
                    $lookingFor     = $searchPreferences['gender'],
                    $ageFrom        = (int) $searchPreferences['age_from'],
                    $ageTo          = (int) $searchPreferences['age_to'],
                    $target         = null,
                    $onlyWithPhoto  = true,
                    $onlyReal       = true,
                    $onlyWithWebCam = false,
                    $noIntim        = true,
                    $countryId      = 3159, //Россия
                    $regionId       = null,
                    $cityId         = null,
                    $metroId        = null,
                    $offset         = $offset + 10,
                    $blocks         = array(),
                    $idsOnly        = true
                );

                if (isset($result['users'])) {
                    foreach ($result['users'] as $currentUserId) {
                        if (is_int($currentUserId) && !$this->getViewedQueueObject()->exists($webUserId, $currentUserId)) {
                            $this->getSearchQueueObject()->put($webUserId, $currentUserId, $this->getEnergyObject()->get($currentUserId))
                                && $usersAddedCount++;

                            if ($usersAddedCount >= self::LIMIT) {
                                break;
                            }
                        }
                    }
                }

                $this->log("[Search queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added;");
            } while (isset($result['users']) && count($result['users']) && $usersAddedCount < self::LIMIT);
        }
    }
}