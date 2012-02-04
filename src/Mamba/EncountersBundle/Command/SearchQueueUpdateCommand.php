<?php
namespace Mamba\EncountersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Mamba\EncountersBundle\Command\QueueUpdateCronScript;
use Mamba\EncountersBundle\EncountersBundle;
use Mamba\EncountersBundle\Popularity;

/**
 * SearchQueueUpdateCommand
 *
 * @package EncountersBundle
 */
class SearchQueueUpdateCommand extends QueueUpdateCronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Search queue updater"
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
        while ($worker->work() && --$this->iterations) {
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

        if ($webUserId = (int) $job->workload()) {
            $Mamba->set('oid', $webUserId);

            if (!$Mamba->getReady()) {
                $this->log("Mamba is not ready!", 16);
                return;
            }
        } else {
            throw new CronScriptException("Invalid workload");
        }

        if (!($searchPreferences = $this->getPreferencesObject()->get($webUserId))) {
            throw new CronScriptException("Could not get search preferences for user_id=$webUserId");
        }

        $offset = -10;
        do {
            $Mamba->multi();

            for ($i=0;$i<10;$i++) {
                $Mamba->Search()->get(
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
            }

            $result = $Mamba->exec();

            $usersAddedCount = 0;

            foreach ($result as $item) {
                if (isset($item['users'])) {
                    foreach ($item['users'] as $userId) {
                        if (!$Redis->hExists(sprintf(EncountersBundle::REDIS_HASH_USER_VIEWED_USERS_KEY, $webUserId), $userId)) {
                            $Redis->zAdd(
                                sprintf(EncountersBundle::REDIS_ZSET_USER_SEARCH_QUEUE_KEY, $webUserId),
                                Popularity::getPopularity($this->getEnergyObject()->get($userId)),
                                $userId
                            ) && $usersAddedCount++;
                        }
                    }
                }
            }

            $this->log("[Search queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added;");
        } while (array_filter($result, function($item) {
            return isset($item['users']) && count($item['users']);
        }));

        $Redis->hSet(
            sprintf(EncountersBundle::REDIS_HASH_USER_CRON_DETAILS_KEY, $webUserId),
            EncountersBundle::REDIS_HASH_KEY_SEARCH_QUEUE_UPDATED,
            time()
        );
    }
}