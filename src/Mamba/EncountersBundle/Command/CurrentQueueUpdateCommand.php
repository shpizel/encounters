<?php
namespace Mamba\EncountersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Mamba\EncountersBundle\CronScript;
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
        SCRIPT_DESCRIPTION = "Current queue update",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:queue:current:update"
    ;

    public static

        /**
         * Баланс
         *
         * @var array
         */
        $balance = array(
            'search'   => 5,
            'priority' => 3,
            'hitlist'  => 1,
            'contacts' => 5,
        )
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
        $worker->addFunction(EncountersBundle::GEARMAN_CURRENT_QUEUE_UPDATE_FUNCTION_NAME, function($job) use($class) {
            try {
                return $class->updateCurrentQueue($job);
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
     * Обновляет пользовательскую очередь
     *
     * @param $job
     */
    public function updateCurrentQueue($job) {
        do {
            list($webUserId, $timestamp) = array_values(unserialize($job->workload()));
            $searchQueueChunk = $this->getSearchQueueObject()->getRange($webUserId, 0, self::$balance['search'] - 1);
            $usersAddedCount = 0;
            foreach ($searchQueueChunk as $currentUserId) {
                $this->getSearchQueueObject()->remove($webUserId, $currentUserId = (int) $currentUserId);
                if (!$this->getViewedQueueObject()->exists($webUserId, $currentUserId)) {
                    $this->getCurrentQueueObject()->put($webUserId, $currentUserId)
                        && $usersAddedCount++;
                }
            }
            $this->log("[Current queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added from search queue;");
            if (!$usersAddedCount) {
                break;
            }

            $priorityCount = self::$balance['priority'];
            $usersAddedCount = 0;
            while ($priorityCount--) {
                if ($currentUserId = $this->getPriorityQueueObject()->pop($webUserId)) {
                    if (!$this->getViewedQueueObject()->exists($webUserId, $currentUserId = (int) $currentUserId)) {
                        $this->getCurrentQueueObject()->put($webUserId, $currentUserId)
                            && $usersAddedCount++;
                    }
                } else {
                    break;
                }
            }
            $this->log("[Current queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added from priority queue;");

            $hitlistCount = self::$balance['hitlist'];
            $usersAddedCount = 0;
            while ($hitlistCount--) {
                if ($currentUserId = $this->getHitlistQueueObject()->pop($webUserId)) {
                    if (!$this->getViewedQueueObject()->exists($webUserId, $currentUserId = (int) $currentUserId)) {
                        $this->getCurrentQueueObject()->put($webUserId, $currentUserId)
                            && $usersAddedCount++;
                    }
                } else {
                    break;
                }
            }
            $this->log("[Current queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added from hitlist queue;");

            $contactsCount = self::$balance['contacts'];
            $usersAddedCount = 0;
            while ($contactsCount--) {
                if ($currentUserId = $this->getContactsQueueObject()->pop($webUserId)) {
                    if (!$this->getViewedQueueObject()->exists($webUserId, $currentUserId = (int) $currentUserId)) {
                        $this->getCurrentQueueObject()->put($webUserId, $currentUserId)
                            && $usersAddedCount++;
                    }
                } else {
                    break;
                }
            }
            $this->log("[Current queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added from contacts queue;");
        }
        while (true);

        $GearmanClient = $this->getGearman()->getClient();

        $GearmanClient->doHighBackground(EncountersBundle::GEARMAN_SEARCH_QUEUE_UPDATE_FUNCTION_NAME, serialize(array(
            'user_id'   => $webUserId,
            'timestamp' => time(),
        )));

        $GearmanClient->doHighBackground(EncountersBundle::GEARMAN_HITLIST_QUEUE_UPDATE_FUNCTION_NAME, serialize(array(
            'user_id'   => $webUserId,
            'timestamp' => time(),
        )));

        $GearmanClient->doHighBackground(EncountersBundle::GEARMAN_CONTACTS_QUEUE_UPDATE_FUNCTION_NAME, serialize(array(
            'user_id'   => $webUserId,
            'timestamp' => time(),
        )));
    }
}