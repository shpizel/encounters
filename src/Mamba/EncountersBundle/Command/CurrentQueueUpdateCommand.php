<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Command\CronScript;
use Mamba\EncountersBundle\EncountersBundle;

use Mamba\EncountersBundle\Command\SearchQueueUpdateCommand;
use Mamba\EncountersBundle\Command\HitlistQueueUpdateCommand;
use Mamba\EncountersBundle\Command\ContactsQueueUpdateCommand;

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
        $worker->addFunction(EncountersBundle::GEARMAN_CURRENT_QUEUE_UPDATE_FUNCTION_NAME, function($job) use($class) {
            try {
                return $class->updateCurrentQueue($job);
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
     * Обновляет пользовательскую очередь
     *
     * @param $job
     */
    public function updateCurrentQueue($job) {
        list($webUserId, $timestamp) = array_values(unserialize($job->workload()));

        do {
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
        while ($this->getCurrentQueueObject()->getSize($webUserId) <= (SearchQueueUpdateCommand::LIMIT + ContactsQueueUpdateCommand::LIMIT + HitlistQueueUpdateCommand::LIMIT));

        /**
         * SearchPreferences GEO
         *
         * @author shpizel
         */
        $searchPreferencesLastChecked = $this->getVariablesObject()->get($webUserId, 'search_preferences_last_checked');
        if (!$searchPreferencesLastChecked || (time() - $searchPreferencesLastChecked > 3600)) {
            if ($searchPreferences = $this->getSearchPreferencesObject()->get($webUserId)) {
                if ($apiResponse = $this->getMamba()->nocache()->Anketa()->getInfo($webUserId)) {
                    if ($anketa = array_shift($apiResponse)) {

                        $searchPreferences['geo']['country_id'] = isset($anketa['location']['country_id']) ? $anketa['location']['country_id'] : $searchPreferences['geo']['country_id'];
                        $searchPreferences['geo']['region_id'] = isset($anketa['location']['region_id']) ? $anketa['location']['region_id'] : $searchPreferences['geo']['region_id'];
                        $searchPreferences['geo']['city_id'] = isset($anketa['location']['city_id']) ? $anketa['location']['city_id'] : $searchPreferences['geo']['city_id'];
                        $searchPreferences['changed'] = time();

                        $this->getSearchPreferencesObject()->set($webUserId, $searchPreferences);
                    }
                }

                $this->getVariablesObject()->set($webUserId, 'search_preferences_last_checked', time());
            }
        }

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