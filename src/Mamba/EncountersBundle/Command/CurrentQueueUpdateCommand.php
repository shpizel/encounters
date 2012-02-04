<?php
namespace Mamba\EncountersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Mamba\EncountersBundle\Command\QueueUpdateCronScript;
use Mamba\EncountersBundle\EncountersBundle;

/**
 * CurrentQueueUpdateCommand
 *
 * @package EncountersBundle
 */
class CurrentQueueUpdateCommand extends QueueUpdateCronScript {

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
            'priority' => 1,
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
        $worker = $this->getGearman()->getWorker();

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_CURRENT_QUEUE_UPDATE_FUNCTION_NAME, function($job) use($class) {
            try {
                return $class->updateCurrentQueue($job);
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
     * Обновляет пользовательскую очередь
     *
     * @param $job
     */
    public function updateCurrentQueue($job) {
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

        /**
         * Наполним очередь согласно балансу
         *
         * @author shpizel
         */
        while (!$Redis->zSize(sprintf(EncountersBundle::REDIS_ZSET_USER_CURRENT_QUEUE_KEY, $webUserId))) {

            $searchQueueChunk = $Redis->zRange(sprintf(EncountersBundle::REDIS_ZSET_USER_SEARCH_QUEUE_KEY,$webUserId), 0, self::$balance['search'] - 1);
            $usersAddedCount = 0;
            foreach ($searchQueueChunk as $userId) {
                var_dump($userId);
                $Redis->zDelete(sprintf(EncountersBundle::REDIS_ZSET_USER_SEARCH_QUEUE_KEY,$webUserId), $userId);
                if (!$Redis->hExists(sprintf(EncountersBundle::REDIS_HASH_USER_VIEWED_USERS_KEY, $webUserId), $userId)) {
                    $Redis->zAdd(sprintf(EncountersBundle::REDIS_ZSET_USER_CURRENT_QUEUE_KEY, $webUserId), 0, $userId) && $usersAddedCount++;
                }
            }
            $this->log("[Current queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added from search queue;");

            $priorityCount = self::$balance['priority'];
            $usersAddedCount = 0;
            while ($priorityCount--) {
                if ($userId = $Redis->sPop(sprintf(EncountersBundle::REDIS_SET_USER_PRIORITY_QUEUE_KEY, $webUserId))) {
                    if (!$Redis->hExists(sprintf(EncountersBundle::REDIS_HASH_USER_VIEWED_USERS_KEY, $webUserId), $userId)) {
                        $Redis->zAdd(sprintf(EncountersBundle::REDIS_ZSET_USER_CURRENT_QUEUE_KEY, $webUserId), 0, $userId) && $usersAddedCount++;
                    }
                } else {
                    break;
                }
            }
            $this->log("[Current queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added from priority queue;");

            $hitlistCount = self::$balance['hitlist'];
            $usersAddedCount = 0;
            while ($hitlistCount--) {
                if ($userId = $Redis->sPop(sprintf(EncountersBundle::REDIS_SET_USER_HITLIST_QUEUE_KEY, $webUserId))) {
                    if (!$Redis->hExists(sprintf(EncountersBundle::REDIS_HASH_USER_VIEWED_USERS_KEY, $webUserId), $userId)) {
                        $Redis->zAdd(sprintf(EncountersBundle::REDIS_ZSET_USER_CURRENT_QUEUE_KEY, $webUserId), 0, $userId) && $usersAddedCount++;
                    }
                } else {
                    break;
                }
            }
            $this->log("[Current queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added from hitlist queue;");

            $contactsCount = self::$balance['contacts'];
            $usersAddedCount = 0;
            while ($contactsCount--) {
                if ($userId = $Redis->sPop(sprintf(EncountersBundle::REDIS_SET_USER_CONTACTS_QUEUE_KEY, $webUserId))) {
                    if (!$Redis->hExists(sprintf(EncountersBundle::REDIS_HASH_USER_VIEWED_USERS_KEY, $webUserId), $userId)) {
                        $Redis->zAdd(sprintf(EncountersBundle::REDIS_ZSET_USER_CURRENT_QUEUE_KEY, $webUserId),  0, $userId) && $usersAddedCount++;
                    }
                } else {
                    break;
                }
            }
            $this->log("[Current queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added from contacts queue;");
        }

        $Redis->hSet(
            sprintf(EncountersBundle::REDIS_HASH_USER_CRON_DETAILS_KEY, $webUserId),
            EncountersBundle::REDIS_HASH_KEY_CURRENT_QUEUE_UPDATED,
            time()
        );
    }
}