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
        SCRIPT_DESCRIPTION = "Search queue updater",

        /**
         * Лимит
         *
         * @var int
         */
        LIMIT = 25
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

        if (!($searchPreferences = $this->getSearchPreferencesObject()->get($webUserId))) {
            throw new CronScriptException("Could not get search preferences for user_id=$webUserId");
        }

        if ($this->getSearchQueueObject()->getSize($webUserId) >= self::LIMIT) {
            return;
        }

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
                    }
                }
            }

            $this->log("[Search queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added;");
        } while (isset($result['users']) && count($result['users']) && $usersAddedCount < self::LIMIT);
    }
}