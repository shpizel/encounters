<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\CronScript;

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
        return $this->getMemcache()->add($this->getLockName(), 1, ($this->daemon) ? 3600 : 60);
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
        $worker = $this->getGearmanWorker();

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_CURRENT_QUEUE_UPDATE_FUNCTION_NAME, function($job) use($class) {
            try {
                return $class->updateCurrentQueue($job);
            } catch (\Exception $e) {
                $class->log("Error: " . static::SCRIPT_NAME . ":" . $e->getCode() . " " . $e->getMessage(), 16);
                $class->unlock();

                return;
            }
        });

        $iterations = $this->iterations;
        while
        (
            (!$this->getMemcache()->get("cron:stop") || (($stopCommandTimeStamp = (int) $this->getMemcache()->get("cron:stop")) && ($stopCommandTimeStamp < $this->started))) &&
            ((time() - $this->started < $this->lifetime) || !$this->lifetime) &&
            filemtime(__FILE__) < $this->started &&
            ((memory_get_usage() < $this->memory) || !$this->memory) &&
            $this->iterations-- &&
            $this->log(($iterations - $this->iterations) . " iteration:", 48) &&
            (@$worker->work() || $worker->returnCode() == GEARMAN_TIMEOUT)
        ) {
            if ($worker->returnCode() == GEARMAN_TIMEOUT) {
                $this->log("Timed out", 48);
                continue;
            } elseif ($worker->returnCode() != GEARMAN_SUCCESS) {
                $this->log("Failed", 16);
                $this->unlock();

                break;
            } elseif ($worker->returnCode() == GEARMAN_SUCCESS) {
                $this->log("Completed", 64);
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

        $this->log("Got task for <info>current_user_id</info> = {$webUserId}, <info>timestamp</info> = {$timestamp}");

        /** Наполняем текущую очередь только если она меньше 32 позиций */
        while ($this->getCurrentQueueHelper()->getSize($webUserId) <= 32) {
            $searchQueueChunk = $this->getSearchQueueHelper()->getRange($webUserId, 0, self::$balance['search'] - 1);
            $usersAddedCount = 0;

            foreach ($searchQueueChunk as $currentUserId) {
                $this->getSearchQueueHelper()->remove($webUserId, $currentUserId = (int) $currentUserId);
                if (!$this->getViewedQueueHelper()->exists($webUserId, $currentUserId)) {
                    $this->getCurrentQueueHelper()->put($webUserId, $currentUserId)
                        && $usersAddedCount++;
                }
            }
            $this->log("<error>$usersAddedCount</error> users were added from search queue");
            if (!$usersAddedCount) {
                break;
            }

            $priorityCount = self::$balance['priority'];
            $usersAddedCount = 0;
            while ($priorityCount--) {
                if ($currentUserId = $this->getPriorityQueueHelper()->pop($webUserId)) {
                    if (!$this->getViewedQueueHelper()->exists($webUserId, $currentUserId = (int) $currentUserId)) {
                        $this->getCurrentQueueHelper()->put($webUserId, $currentUserId)
                            && $usersAddedCount++;
                    }
                } else {
                    break;
                }
            }
            $this->log("<error>$usersAddedCount</error> users were added from priority queue");

            $hitlistCount = self::$balance['hitlist'];
            $usersAddedCount = 0;
            while ($hitlistCount--) {
                if ($currentUserId = $this->getHitlistQueueHelper()->pop($webUserId)) {
                    if (!$this->getViewedQueueHelper()->exists($webUserId, $currentUserId = (int) $currentUserId)) {
                        $this->getCurrentQueueHelper()->put($webUserId, $currentUserId)
                            && $usersAddedCount++;
                    }
                } else {
                    break;
                }
            }
            $this->log("<error>$usersAddedCount</error> users were added from hitlist queue");

            $contactsCount = self::$balance['contacts'];
            $usersAddedCount = 0;
            while ($contactsCount--) {
                if ($currentUserId = $this->getContactsQueueHelper()->pop($webUserId)) {
                    if (!$this->getViewedQueueHelper()->exists($webUserId, $currentUserId = (int) $currentUserId)) {
                        $this->getCurrentQueueHelper()->put($webUserId, $currentUserId)
                            && $usersAddedCount++;
                    }
                } else {
                    break;
                }
            }
            $this->log("<error>$usersAddedCount</error> users were added from contacts queue");
        }

        /**
         * SearchPreferences GEO
         *
         * @author shpizel
         */
        $searchPreferencesLastChecked = $this->getVariablesHelper()->get($webUserId, 'search_preferences_last_checked');
        if (!$searchPreferencesLastChecked || (time() - $searchPreferencesLastChecked > 3600)) {
            if ($searchPreferences = $this->getSearchPreferencesHelper()->get($webUserId)) {
                if ($apiResponse = $this->getMamba()->nocache()->Anketa()->getInfo($webUserId)) {
                    if ($anketa = array_shift($apiResponse)) {

                        $searchPreferences['geo']['country_id'] = isset($anketa['location']['country_id']) ? $anketa['location']['country_id'] : $searchPreferences['geo']['country_id'];
                        $searchPreferences['geo']['region_id'] = isset($anketa['location']['region_id']) ? $anketa['location']['region_id'] : $searchPreferences['geo']['region_id'];
                        $searchPreferences['geo']['city_id'] = isset($anketa['location']['city_id']) ? $anketa['location']['city_id'] : $searchPreferences['geo']['city_id'];
                        $searchPreferences['changed'] = time();

                        $this->getSearchPreferencesHelper()->set($webUserId, $searchPreferences);

                        $this->getGearmanClient()->doHighBackground(
                            EncountersBundle::GEARMAN_DATABASE_USERS_UPDATE_FUNCTION_NAME,
                            serialize(
                                array(
                                    'users' => [$webUserId],
                                    'time'  => time(),
                                )
                            )
                        );
                    }
                }

                $this->getVariablesHelper()->set($webUserId, 'search_preferences_last_checked', time());
            }
        }

        $dataArray = serialize(
            array(
                'user_id'   => $webUserId,
                'timestamp' => time(),
            )
        );

        $GearmanClient = $this->getGearmanClient();

        foreach ([EncountersBundle::GEARMAN_SEARCH_QUEUE_UPDATE_FUNCTION_NAME,
            EncountersBundle::GEARMAN_HITLIST_QUEUE_UPDATE_FUNCTION_NAME,
            EncountersBundle::GEARMAN_CONTACTS_QUEUE_UPDATE_FUNCTION_NAME,
        ] as $functionName) {
            $GearmanClient->doHighBackground($functionName, $dataArray);
        }
    }
}