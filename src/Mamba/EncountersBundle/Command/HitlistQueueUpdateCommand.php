<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\CronScript;
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
        SCRIPT_DESCRIPTION = "Hitlist queue update",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:queue:hitlist:update",

        /**
         * Лимит
         *
         * @var int
         */
        LIMIT = 8
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
        $worker->addFunction(EncountersBundle::GEARMAN_HITLIST_QUEUE_UPDATE_FUNCTION_NAME, function($job) use($class) {
            try {
                return $class->updateHitlistQueue($job);
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
                $this->log("Success", 16);
                break;
            }
        }
    }

    /**
     * Обновляет пользовательскую очередь из хитлиста
     *
     * @param $job
     */
    public function updateHitlistQueue($job) {
        $Mamba = $this->getMamba();

        list($webUserId, $timestamp) = array_values(unserialize($job->workload()));
        if ($webUserId = (int) $webUserId) {
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

        if ($searchPreferences['changed'] > $timestamp) {
            return;
        }

        if ($this->getHitlistQueueObject()->getSize($webUserId) >= self::LIMIT) {
            return;
        }

        if ($hitList = $Mamba->Anketa()->getHitlist(-30)) {
            $usersAddedCount = 0;
            foreach ($hitList['visitors'] as $user) {
                $userInfo = $user['info'];
                list($currentUserId, $gender, $age) = array($userInfo['oid'], $userInfo['gender'], $userInfo['age']);

                if (isset($userInfo['medium_photo_url']) && $userInfo['medium_photo_url']) {
                    if ($gender == $searchPreferences['gender']) {
                        if (!$age || ($age >= $searchPreferences['age_from'] && $age <= $searchPreferences['age_to'])) {

                            if (is_int($currentUserId) && !$this->getViewedQueueObject()->exists($webUserId, $currentUserId)) {
                                $this->getHitlistQueueObject()->put($webUserId, $currentUserId)
                                    && $usersAddedCount++;

                                if ($usersAddedCount >= self::LIMIT) {
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            $this->log("[Hitlist queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added;");
        } else {
            throw new CronScriptException("Could not fetch hitlist for user_id=$webUserId");
        }
    }
}