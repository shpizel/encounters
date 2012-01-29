<?php
namespace Mamba\EncountersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Mamba\EncountersBundle\EncountersBundle;

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
        SCRIPT_DESCRIPTION = "Search queue updater"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $worker = $this->getContainer()->get('gearman')->getWorker();

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_SEARCH_QUEUE_UPDATE_FUNCTION_NAME, function($job) use($class) {
            try {
                return $class->updateSearchQueue($job);
            } catch (\Exception $e) {
                return;
            }
        });

        while ($worker->work() && $this->iterations) {
            if ($worker->returnCode() != GEARMAN_SUCCESS) {
                break;
            }

            $this->iterations--;
        }
    }

    /**
     * Обновляет пользовательскую очередь из поиска
     *
     * @param $job
     */
    public function updateSearchQueue($job) {
        $Mamba = $this->getContainer()->get('mamba');
        if (list($userId, $limit) = unserialize($job->workload())) {
            $Mamba->set('oid', $userId);
            if (!$Mamba->getReady()) {
                return;
            }
        } else {
            return;
        }

        $Redis = $this->getContainer()->get('redis');
        if (!($searchPreferences = $this->getSearchPreferences())) {
            return;
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
            foreach ($result as $item) {
                if (isset($item['users'])) {
                    foreach ($item['users'] as $userId) {
                        if (!$Redis->hExists(sprintf(EncountersBundle::REDIS_HASH_USER_VIEWED_USERS_KEY, $Mamba->get('oid')), $userId)) {
                            $Redis->zAdd(sprintf(EncountersBundle::REDIS_ZSET_USER_SEARCH_QUEUE_KEY, $Mamba->get('oid')), 1, $userId);
                        }
                    }
                }
            }
        } while (array_filter($result, function($item) {
            return isset($item['users']) && count($item['users']);
        }));

        $Redis->set(sprinft(EncountersBundle::REDIS_USER_LAST_SEARCH_QUEUE_UPDATED_KEY, $Mamba->get('oid')), time());
    }

    /**
     * Возвращает поисковые преференции для текущего юзера
     *
     * @return mixed
     */
    private function getSearchPreferences() {
        return
            $this->getContainer()->get('redis')
                ->hGetAll(
                    sprintf(EncountersBundle::REDIS_HASH_USER_SEARCH_PREFERENCES_KEY,
                    $this->getContainer()->get('mamba')->get('oid'))
                )
        ;
    }
}