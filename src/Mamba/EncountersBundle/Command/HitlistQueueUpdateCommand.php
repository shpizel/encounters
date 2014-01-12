<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\CronScript;

use Mamba\EncountersBundle\EncountersBundle;

use Mamba\EncountersBundle\Command\ContactsQueueUpdateCommand;
use Mamba\EncountersBundle\Command\SearchQueueUpdateCommand;
use Mamba\EncountersBundle\Command\CurrentQueueUpdateCommand;

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
        LIMIT = 32
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
        $worker->addFunction(EncountersBundle::GEARMAN_HITLIST_QUEUE_UPDATE_FUNCTION_NAME, function($job) use($class) {
            try {
                return $class->updateHitlistQueue($job);
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
     * Обновляет пользовательскую очередь из хитлиста
     *
     * @param $job
     */
    public function updateHitlistQueue($job) {
        $Mamba = $this->getMamba();
        list($webUserId, $timestamp) = array_values(unserialize($job->workload()));

        $this->currentUserId = $webUserId;
        if (!$this->lock()) {
            throw new \Core\ScriptBundle\CronScriptException("Could not obtain lock");
        }

        if ($webUserId = (int) $webUserId) {
            $Mamba->set('oid', $webUserId);

            if (!$Mamba->getReady()) {
                throw new \Core\ScriptBundle\CronScriptException("Mamba is not ready!");
            }
        } else {
            throw new \Core\ScriptBundle\CronScriptException("Invalid workload");
        }

        if (!($searchPreferences = $this->getSearchPreferencesHelper()->get($webUserId))) {
            throw new \Core\ScriptBundle\CronScriptException("Could not get search preferences for user_id=$webUserId");
        }

        if ($searchPreferences['changed'] > $timestamp) {
            return;
        }

        if ($this->getHitlistQueueHelper()->getSize($webUserId) >= self::LIMIT) {
            throw new \Core\ScriptBundle\CronScriptException("Hitlist queue for user_id=$webUserId has limit exceed");
        }

        if ($hitList = $Mamba->Anketa()->getHitlist(-30)) {
            $usersAddedCount = 0;
            foreach ($hitList['visitors'] as $user) {
                $userInfo = $user['info'];
                list($currentUserId, $gender, $age) = array($userInfo['oid'], $userInfo['gender'], $userInfo['age']);

                if (isset($userInfo['medium_photo_url']) && $userInfo['medium_photo_url']) {
                    if ($gender == $searchPreferences['gender']) {
                        if (!$age || ($age >= $searchPreferences['age_from'] && $age <= $searchPreferences['age_to'])) {

                            if (is_int($currentUserId) && !$this->getViewedQueueHelper()->exists($webUserId, $currentUserId) && !$this->getCurrentQueueHelper()->exists($webUserId, $currentUserId)) {
                                $this->getHitlistQueueHelper()->put($webUserId, $currentUserId)
                                    && $usersAddedCount++;

                                if ($usersAddedCount >= self::LIMIT) {
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            $this->log("<error>$usersAddedCount</error> users were added to hitlist queue for <info>user_id</info> = {$webUserId}");
        } else {
            throw new \Core\ScriptBundle\CronScriptException("Could not fetch hitlist for user_id=$webUserId");
        }
    }
}