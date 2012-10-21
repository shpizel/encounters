<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\CronScript;

use Mamba\EncountersBundle\EncountersBundle;

use Mamba\EncountersBundle\Command\SearchQueueUpdateCommand;
use Mamba\EncountersBundle\Command\HitlistQueueUpdateCommand;

/**
 * ContactsQueueUpdateCommand
 *
 * @package EncountersBundle
 */
class ContactsQueueUpdateCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Contacts queue update",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:queue:contacts:update",

        /**
         * Лимит
         *
         * @var int
         */
        LIMIT = 8
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
        $worker->addFunction(EncountersBundle::GEARMAN_CONTACTS_QUEUE_UPDATE_FUNCTION_NAME, function($job) use($class) {
            try {
                return $class->updateContactsQueue($job);
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
     * Обновляет пользовательскую очередь из контактов
     *
     * @param $job
     */
    public function updateContactsQueue($job) {
        $Mamba = $this->getMamba();
        list($webUserId, $timestamp) = array_values(unserialize($job->workload()));

        $this->log("Got task for <info>current_user_id</info> = {$webUserId}, <info>timestamp</info> = {$timestamp}");

        $this->currentUserId = $webUserId;
        if (!$this->lock()) {
            throw new \Core\ScriptBundle\CronScriptException("Could not obtain lock");
        }

        if ($webUserId = (int) $webUserId) {
            $Mamba->set('oid', $webUserId);

            if (!$Mamba->getReady()) {
                $this->log("Mamba is not ready!", 16);
                return;
            }
        } else {
            throw new \Core\ScriptBundle\CronScriptException("Invalid workload");
        }

        if (!($searchPreferences = $this->getSearchPreferencesObject()->get($webUserId))) {
            throw new \Core\ScriptBundle\CronScriptException("Could not get search preferences for user_id=$webUserId");
        }

        if ($searchPreferences['changed'] > $timestamp) {
            return;
        }

        if ($this->getContactsQueueObject()->getSize($webUserId) >= self::LIMIT) {
            throw new \Core\ScriptBundle\CronScriptException("Contacts queue for user_id=$webUserId has limit exceed");
        }

        if ($contactsFolders = $Mamba->Contacts()->getFolderList()) {
            $usersAddedCount = 0;

            foreach ($contactsFolders['folders'] as $folder) {
                if ($contactList = $Mamba->Contacts()->getFolderContactList($folder['folder_id'])) {
                    foreach ($contactList['contacts'] as $contact) {
                        $contactInfo = $contact['info'];
                        list($currentUserId, $gender, $age) = array($contactInfo['oid'], $contactInfo['gender'], $contactInfo['age']);
                        if (isset($contactInfo['medium_photo_url']) && $contactInfo['medium_photo_url']) {
                            if ($gender == $searchPreferences['gender']) {
                                if (is_int($currentUserId) && !$this->getViewedQueueObject()->exists($webUserId, $currentUserId) && !$this->getCurrentQueueObject()->exists($webUserId, $currentUserId)) {
                                    if ($this->getContactsQueueObject()->put($webUserId, $currentUserId)) {

                                        /** Ключ ставим только в случае, если приложение у юзера не стоит */
                                        if (($appUser = $Mamba->Anketa()->isAppUser($currentUserId)) && (!$appUser[0]['is_app_user'])) {
                                            $this->getMemcache()->add(
                                                "contacts_queue_{$webUserId}_{$currentUserId}",
                                                time(),
                                                30 * 24 * 3600
                                            );
                                        }

                                        $usersAddedCount++;
                                    }

                                    if ($usersAddedCount >= self::LIMIT) {
                                        break 2;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $this->log("<error>$usersAddedCount</error> users were added to contacts queue for <info>user_id</info> = {$webUserId}");
        } else {
            throw new \Core\ScriptBundle\CronScriptException("Could not fetch contact list for user_id=$webUserId");
        }
    }
}