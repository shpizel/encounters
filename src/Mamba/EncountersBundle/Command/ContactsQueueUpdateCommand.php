<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Command\CronScript;
use Mamba\EncountersBundle\EncountersBundle;

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

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $worker = $this->getGearman()->getWorker();
        $worker->setTimeout(static::GEARMAN_WORKER_TIMEOUT);

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_CONTACTS_QUEUE_UPDATE_FUNCTION_NAME, function($job) use($class) {
            try {
                return $class->updateContactsQueue($job);
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
            $this->iterations-- &&
            (@$worker->work() || $worker->returnCode() == GEARMAN_TIMEOUT)
        ) {
            if ($worker->returnCode() == GEARMAN_TIMEOUT) {
                $this->log(($this->iterations + 1) . ") Timed out (".  round(memory_get_usage(true)/1024/1024, 2) . "M/" . (time() - $this->started) . "s)", 48);
                continue;
            } elseif ($worker->returnCode() != GEARMAN_SUCCESS) {
                $this->log(($this->iterations + 1) . ") Failed (".  round(memory_get_usage(true)/1024/1024, 2) . "M/" . (time() - $this->started) . "s)", 16);
                break;
            } elseif ($worker->returnCode() == GEARMAN_SUCCESS) {
                $this->log(($this->iterations + 1) . ") Success (".  round(memory_get_usage(true)/1024/1024, 2) . "M/" . (time() - $this->started) . "s)", 64);
            }
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

        if ($this->getContactsQueueObject()->getSize($webUserId) >= self::LIMIT) {
            throw new CronScriptException("Contacts queue for user_id=$webUserId has limit exceed");
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
                                if (is_int($currentUserId) && !$this->getViewedQueueObject()->exists($webUserId, $currentUserId)) {
                                    $this->getContactsQueueObject()->put($webUserId, $currentUserId)
                                        && $usersAddedCount++;

                                    if ($usersAddedCount >= self::LIMIT) {
                                        break 2;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $this->log("[Contacts queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added;");
        } else {
            throw new CronScriptException("Could not fetch contact list for user_id=$webUserId");
        }
    }
}