<?php
namespace Mamba\EncountersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Mamba\EncountersBundle\EncountersBundle;

/**
 * HitlistQueueUpdateCommand
 *
 * @package EncountersBundle
 */
class HitlistQueueUpdateCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Hitlist queue updater"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $this->getContainer()->get('gearman')->getClient()->doHighBackground(EncountersBundle::GEARMAN_HITLIST_QUEUE_UPDATE_FUNCTION_NAME, 560015854);
        $worker = $this->getContainer()->get('gearman')->getWorker();

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_HITLIST_QUEUE_UPDATE_FUNCTION_NAME, function($job) use($class) {
            try {
                return $class->updateHitlistQueue($job);
            } catch (\Exception $e) {
                $this->log($e->getMessage());
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
     * Обновляет пользовательскую очередь из хитлиста
     *
     * @param $job
     */
    public function updateHitlistQueue($job) {
        $Mamba = $this->getContainer()->get('mamba');
        if ($userId = (int) $job->workload()) {
            $Mamba->set('oid', $userId);
            if (!$Mamba->getReady()) {
                $this->log("Mamba is not ready");
                return;
            }
        } else {
            $this->log("Could not get user id");
            return;
        }

        $Redis = $this->getContainer()->get('redis');
        if (!($searchPreferences = $this->getSearchPreferences())) {
            $this->log("Could not get search preferences");
            return;
        }

        if ($hitList = $Mamba->Anketa()->getHitlist()) {
            foreach ($hitList['visitors'] as $user) {
                $userInfo = $user['info'];
                list($userId, $gender, $age) = array($userInfo['oid'], $userInfo['gender'], $userInfo['age']);

                if (isset($userInfo['medium_photo_url']) && $userInfo['medium_photo_url']) {
                    if ($gender == $searchPreferences['gender']) {
                        if (!$age || ($age >= $searchPreferences['age_from'] && $age <= $searchPreferences['age_to'])) {
                            if (!$Redis->hExists(sprintf(EncountersBundle::REDIS_HASH_USER_VIEWED_USERS_KEY, $Mamba->get('oid')), $userId)) {
                                $Redis->zAdd(sprintf(EncountersBundle::REDIS_ZSET_USER_HITLIST_QUEUE_KEY, $Mamba->get('oid')), 1, $userId);
                            }
                        }
                    }
                }
            }

            $Redis->hSet(
                sprintf(EncountersBundle::REDIS_HASH_USER_CRON_DETAILS_KEY, $Mamba->get('oid')),
                EncountersBundle::REDIS_HASH_KEY_HITLIST_QUEUE_UPDATED,
                time()
            );
        } else {
            $this->log("Could not get hitlist");
        }
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