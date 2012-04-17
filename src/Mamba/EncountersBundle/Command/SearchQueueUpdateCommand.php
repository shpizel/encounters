<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Command\CronScript;
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
                e.energy > 0 AND
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
                e.energy > 0 AND
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
                e.energy > 0 AND
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
        return $this->getMemcache()->add($this->getLockName(), 1, 5*60);
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
        $worker = $this->getGearman()->getWorker();
        $worker->setTimeout(static::GEARMAN_WORKER_TIMEOUT);

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

        while
        (
            !$this->getMemcache()->get("cron:stop") &&
            ((time() - $this->started < $this->lifetime) || !$this->lifetime) &&
            ((memory_get_usage() < $this->memory) || !$this->memory) &&
            $this->iterations-- &&
            (@$worker->work() || $worker->returnCode() == GEARMAN_TIMEOUT)
        ) {
            if ($worker->returnCode() == GEARMAN_TIMEOUT) {
                $this->log(($this->iterations + 1) . ") Timed out (".  round(memory_get_usage(true)/1024/1024, 2) . "M/" . (time() - $this->started) . "s)", 48);
                continue;
            } elseif ($worker->returnCode() != GEARMAN_SUCCESS) {
                $this->log(($this->iterations + 1) . ") Failed (".  round(memory_get_usage(true)/1024/1024, 2) . "M/" . (time() - $this->started) . "s)", 16);
                $this->unlock();

                break;
            } elseif ($worker->returnCode() == GEARMAN_SUCCESS) {
                $this->log(($this->iterations + 1) . ") Success (".  round(memory_get_usage(true)/1024/1024, 2) . "M/" . (time() - $this->started) . "s)", 64);
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

        $this->currentUserId = $webUserId;
        if (!$this->lock()) {
            throw new CronScriptException("Could not obtain lock");
        }

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
            throw new CronScriptException("Search queue for user_id=$webUserId has limit exceed");
        }

        if ($this->getCurrentQueueObject()->getSize($webUserId) >= (SearchQueueUpdateCommand::LIMIT + ContactsQueueUpdateCommand::LIMIT + HitlistQueueUpdateCommand::LIMIT)) {
            throw new CronScriptException("Current queue for user_id=$webUserId has limit exceed");
        }

        /**
         * 
         *
         * @author shpizel
         */

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

                    if (!$this->getMemcache()->get("invalid_photos_by_{$currentUserId}")) {
                        try {
                            $this->getMamba()->Photos()->get($currentUserId);

                            $this->getSearchQueueObject()->put($webUserId, $currentUserId, $this->getEnergyObject()->get($currentUserId))
                                && $usersAddedCount++;

                            if ($usersAddedCount >= self::LIMIT) {
                                break;
                            }
                        } catch (\Exception $e) {
                            $this->getMemcache()->set("invalid_photos_by_{$currentUserId}", 1, 86400);
                        }
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

                        if (!$this->getMemcache()->get("invalid_photos_by_{$currentUserId}")) {
                            try {
                                $this->getMamba()->Photos()->get($currentUserId);

                                $this->getSearchQueueObject()->put($webUserId, $currentUserId, $this->getEnergyObject()->get($currentUserId))
                                    && $usersAddedCount++;

                                if ($usersAddedCount >= self::LIMIT) {
                                    break;
                                }
                            } catch (\Exception $e) {
                                $this->getMemcache()->set("invalid_photos_by_{$currentUserId}", 1, 86400);
                            }
                        }
                    }
                }

                $this->log("[Search queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added;");
            }
        }

        /** Никого не нашли — ищем по стране и региону */
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

                        if (!$this->getMemcache()->get("invalid_photos_by_{$currentUserId}")) {
                            try {
                                $this->getMamba()->Photos()->get($currentUserId);

                                $this->getSearchQueueObject()->put($webUserId, $currentUserId, $this->getEnergyObject()->get($currentUserId))
                                    && $usersAddedCount++;

                                if ($usersAddedCount >= self::LIMIT) {
                                    break;
                                }
                            } catch (\Exception $e) {
                                $this->getMemcache()->set("invalid_photos_by_{$currentUserId}", 1, 86400);
                            }
                        }
                    }
                }

                $this->log("[Search queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added;");
            }
        }

        /** Никого не нашли — ищем по стране */
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