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
            'search'   => 7,
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

        $GearmanClient = $this->getGearman()->getClient();

        $GearmanClient->doHighBackground(EncountersBundle::GEARMAN_HITLIST_QUEUE_UPDATE_FUNCTION_NAME, serialize(array(
            'user_id'   => $webUserId,
            'timestamp' => time(),
        )));

        $GearmanClient->doHighBackground(EncountersBundle::GEARMAN_CONTACTS_QUEUE_UPDATE_FUNCTION_NAME, serialize(array(
            'user_id'   => $webUserId,
            'timestamp' => time(),
        )));

        $GearmanClient->doHighBackground(EncountersBundle::GEARMAN_SEARCH_QUEUE_UPDATE_FUNCTION_NAME, serialize(array(
            'user_id'   => $webUserId,
            'timestamp' => time(),
        )));
    }
}