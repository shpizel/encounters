<?php
namespace Mamba\EncountersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Mamba\EncountersBundle\EncountersBundle;

/**
 * CurrentQueueUpdateCommand
 *
 * @package EncountersBundle
 */
class CurrentQueueUpdateCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Current queue updater"
    ;

    public static

        /**
         * Баланс
         *
         * @var array
         */
        $balance = array(
            'search'   => 5 /** 7 */,
            'main'     => 1,
            'hitlist'  => 1,
            'contacts' => 1,
        )
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $worker = $this->getContainer()->get('gearman')->getWorker();

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_CURRENT_QUEUE_UPDATE_FUNCTION_NAME, function($job) use($class) {
            try {
                return $class->updateCurrentQueue($job);
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
     * Обновляет пользовательскую очередь
     *
     * @param $job
     */
    public function updateCurrentQueue($job) {
        $Mamba = $this->getContainer()->get('mamba');
        if ($userId = (int)$job->workload()) {
            $Mamba->set('oid', $userId);
            if (!$Mamba->getReady()) {
                return;
            }
        } else {
            return;
        }

        $Redis = $this->getContainer()->get('redis');

        /**
         * Наполним очередь согласно балансу
         *
         * @author shpizel
         */
        while (!$Redis->zSize(sprintf(EncountersBundle::REDIS_ZSET_USER_CURRENT_QUEUE_KEY, $Mamba->get('oid')))) {
            $searchQueueChunk   = $Redis->zRange(sprintf(EncountersBundle::REDIS_ZSET_USER_SEARCH_QUEUE_KEY, $Mamba->get('oid')), 0, self::$balance['search'] -1);
            $mainQueueChunk     = $Redis->zRange(sprintf(EncountersBundle::REDIS_ZSET_USER_PRIORITY_QUEUE_KEY, $Mamba->get('oid')), 0, self::$balance['main'] - 1);
            $hitlistQueueChunk  = $Redis->zRange(sprintf(EncountersBundle::REDIS_ZSET_USER_HITLIST_QUEUE_KEY, $Mamba->get('oid')), 0, self::$balance['search'] - 1);
            $contactsQueueChunk = $Redis->zRange(sprintf(EncountersBundle::REDIS_ZSET_USER_CONTACTS_QUEUE_KEY, $Mamba->get('oid')), 0, self::$balance['search'] - 1);

            foreach ($searchQueueChunk as $userId) {
                $Redis->zDelete(sprintf(EncountersBundle::REDIS_ZSET_USER_SEARCH_QUEUE_KEY, $Mamba->get('oid')), $userId);
            }

            foreach ($mainQueueChunk as $userId) {
                $Redis->zDelete(sprintf(EncountersBundle::REDIS_ZSET_USER_PRIORITY_QUEUE_KEY, $Mamba->get('oid')), $userId);
            }

            foreach ($hitlistQueueChunk as $userId) {
                $Redis->zDelete(sprintf(EncountersBundle::REDIS_ZSET_USER_HITLIST_QUEUE_KEY, $Mamba->get('oid')), $userId);
            }

            foreach ($contactsQueueChunk as $userId) {
                $Redis->zDelete(sprintf(EncountersBundle::REDIS_ZSET_USER_CONTACTS_QUEUE_KEY, $Mamba->get('oid')), $userId);
            }

            $currentQueueIds = array_merge($searchQueueChunk, $mainQueueChunk, $hitlistQueueChunk, $contactsQueueChunk);
            foreach ($currentQueueIds as $userId) {
                if (!$Redis->hExists(sprintf(EncountersBundle::REDIS_HASH_USER_VIEWED_USERS_KEY, $Mamba->get('oid')), $userId)) {
                    $Redis->zAdd(sprintf(EncountersBundle::REDIS_ZSET_USER_CURRENT_QUEUE_KEY, $Mamba->get('oid')), 1, $userId);
                }
            }
        }

        $Redis->hSet(
            sprintf(EncountersBundle::REDIS_HASH_USER_CRON_DETAILS_KEY, $Mamba->get('oid')),
            EncountersBundle::REDIS_HASH_KEY_CURRENT_QUEUE_UPDATED,
            time()
        );
    }
}