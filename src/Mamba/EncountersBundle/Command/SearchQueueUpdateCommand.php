<?php
namespace Mamba\EncountersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Mamba\EncountersBundle\Command\CronScript;
use Mamba\EncountersBundle\EncountersBundle;
use Mamba\EncountersBundle\Popularity;
use Doctrine\ORM\Query\ResultSetMapping;

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
                u.gender = ? AND
                (u.age = 0 OR (u.age >= ? AND u.age <= ?)) AND

                u.country_id = ? AND
                u.region_id = ? AND
                u.city_id = ?
            ORDER BY
                e.energy DESC
            LIMIT
              1024
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
                u.gender = ? AND
                (u.age = 0 OR (u.age >= ? AND u.age <= ?)) AND

                u.country_id = ? AND
                u.region_id = ?
            ORDER BY
                e.energy DESC
            LIMIT
                1024
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
                u.gender = ? AND
                (u.age = 0 OR (u.age >= ? AND u.age <= ?)) AND

                u.country_id = ?
            ORDER BY
                e.energy DESC
            LIMIT
                1024
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

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_SEARCH_QUEUE_UPDATE_FUNCTION_NAME, function($job) use($class) {
            try {
                return $class->updateSearchQueue($job);
            } catch (\Exception $e) {
                $class->log($e->getCode() . ": " . $e->getMessage(), 16);
                return;
            }
        });

        $this->log("Iterations: {$this->iterations}", 64);
        while ($worker->work() && --$this->iterations && !$this->getMemcache()->get("cron:stop")) {
            $this->log("Iterations: {$this->iterations}", 64);

            if ($worker->returnCode() != GEARMAN_SUCCESS) {
                break;
            }
        }
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
        $rsm = new ResultSetMapping;
        $rsm->addScalarResult('user_id', 'user_id');
        $rsm->addScalarResult('energy', 'energy');
        $query = $this->getEntityManager()->createNativeQuery(self::FULL_SEARCH_SQL, $rsm);
        $query->setParameter(1, $searchPreferences['gender']);
        $query->setParameter(2, $searchPreferences['age_from']);
        $query->setParameter(3, $searchPreferences['age_to']);
        $query->setParameter(4, $searchPreferences['geo']['country_id']);
        $query->setParameter(5, $searchPreferences['geo']['region_id']);
        $query->setParameter(6, $searchPreferences['geo']['city_id']);
        if ($result = $query->getResult()) {
            foreach ($result as $item) {
                if (!$this->getViewedQueueObject()->exists($webUserId, $currentUserId = (int) $item['user_id'])) {
                    $this->getSearchQueueObject()->put($webUserId, $currentUserId, $this->getEnergyObject()->get($currentUserId))
                        && $usersAddedCount++;

                    if ($usersAddedCount >= self::LIMIT) {
                        break;
                    }
                }
            }

            $this->log("[Search queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added;");
        }

        if ($usersAddedCount < self::LIMIT) {

            /** Ищем по базе, страна и регион */
            $rsm = new ResultSetMapping;
            $rsm->addScalarResult('user_id', 'user_id');
            $rsm->addScalarResult('energy', 'energy');
            $query = $this->getEntityManager()->createNativeQuery(self::COUNTRY_AND_REGION_SEARCH_SQL, $rsm);
            $query->setParameter(1, $searchPreferences['gender']);
            $query->setParameter(2, $searchPreferences['age_from']);
            $query->setParameter(3, $searchPreferences['age_to']);
            $query->setParameter(4, $searchPreferences['geo']['country_id']);
            $query->setParameter(5, $searchPreferences['geo']['region_id']);

            if ($result = $query->getResult()) {
                foreach ($result as $item) {
                    if (!$this->getViewedQueueObject()->exists($webUserId, $currentUserId = (int) $item['user_id'])) {
                        $this->getSearchQueueObject()->put($webUserId, $currentUserId, $this->getEnergyObject()->get($currentUserId))
                            && $usersAddedCount++;

                        if ($usersAddedCount >= self::LIMIT) {
                            break;
                        }
                    }
                }
            }

            $this->log("[Search queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added;");
        }

        if ($usersAddedCount < self::LIMIT) {

            /** Ищем по базе, страна и регион */
            $rsm = new ResultSetMapping;
            $rsm->addScalarResult('user_id', 'user_id');
            $rsm->addScalarResult('energy', 'energy');
            $query = $this->getEntityManager()->createNativeQuery(self::COUNTRY_SEARCH_SQL, $rsm);
            $query->setParameter(1, $searchPreferences['gender']);
            $query->setParameter(2, $searchPreferences['age_from']);
            $query->setParameter(3, $searchPreferences['age_to']);
            $query->setParameter(4, $searchPreferences['geo']['country_id']);

            if ($result = $query->getResult()) {
                foreach ($result as $item) {
                    if (!$this->getViewedQueueObject()->exists($webUserId, $currentUserId = (int) $item['user_id'])) {
                        $this->getSearchQueueObject()->put($webUserId, $currentUserId, $this->getEnergyObject()->get($currentUserId))
                            && $usersAddedCount++;

                        if ($usersAddedCount >= self::LIMIT) {
                            break;
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
                    $ageFrom        = $searchPreferences['age_from'],
                    $ageTo          = $searchPreferences['age_to'],
                    $target         = null,
                    $onlyWithPhoto  = true,
                    $onlyReal       = true,
                    $onlyWithWebCam = false,
                    $noIntim        = true,
                    $countryId      = $searchPreferences['geo']['country_id'],
                    $regionId       = $searchPreferences['geo']['region_id'],
                    $cityId         = $searchPreferences['geo']['city_id'],
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

        /** Никого не нашли — ищем по стране и региону */
        if ($usersAddedCount < self::LIMIT) {

            $offset = -10;
            $usersAddedCount = 0;
            do {
                $result = $Mamba->Search()->get(
                    $whoAmI         = null,
                    $lookingFor     = $searchPreferences['gender'],
                    $ageFrom        = $searchPreferences['age_from'],
                    $ageTo          = $searchPreferences['age_to'],
                    $target         = null,
                    $onlyWithPhoto  = true,
                    $onlyReal       = true,
                    $onlyWithWebCam = false,
                    $noIntim        = true,
                    $countryId      = $searchPreferences['geo']['country_id'],
                    $regionId       = $searchPreferences['geo']['region_id'],
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

        /** Никого не нашли — ищем по стране */
        if ($usersAddedCount < self::LIMIT) {

            $offset = -10;
            $usersAddedCount = 0;
            do {
                $result = $Mamba->Search()->get(
                    $whoAmI         = null,
                    $lookingFor     = $searchPreferences['gender'],
                    $ageFrom        = $searchPreferences['age_from'],
                    $ageTo          = $searchPreferences['age_to'],
                    $target         = null,
                    $onlyWithPhoto  = true,
                    $onlyReal       = true,
                    $onlyWithWebCam = false,
                    $noIntim        = true,
                    $countryId      = $searchPreferences['geo']['country_id'],
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
                    $ageFrom        = $searchPreferences['age_from'],
                    $ageTo          = $searchPreferences['age_to'],
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