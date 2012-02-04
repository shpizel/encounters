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
 * ContactsQueueUpdateCommand
 *
 * @package EncountersBundle
 */
class ContactsQueueUpdateCommand extends QueueUpdateCronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Contacts queue updater"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $worker = $this->getGearman()->getWorker();

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_CONTACTS_QUEUE_UPDATE_FUNCTION_NAME, function($job) use($class) {
            try {
                return $class->updateContactsQueue($job);
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
     * Обновляет пользовательскую очередь из контактов
     *
     * @param $job
     */
    public function updateContactsQueue($job) {
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

        if (!($searchPreferences = $this->getPreferencesObject()->get($webUserId))) {
            throw new CronScriptException("Could not get search preferences for user_id=$webUserId");
        }

        if ($contactList = $Mamba->Contacts()->getContactList()) {
            $usersAddedCount = 0;

            foreach ($contactList['contacts'] as $contact) {
                $contactInfo = $contact['info'];
                list($userId, $gender, $age) = array($contactInfo['oid'], $contactInfo['gender'], $contactInfo['age']);
                if (isset($contactInfo['medium_photo_url']) && $contactInfo['medium_photo_url']) {
                    if ($gender == $searchPreferences['gender']) {
                        if (!$age || ($age >= $searchPreferences['age_from'] && $age <= $searchPreferences['age_to'])) {
                            if (!$Redis->hExists(sprintf(EncountersBundle::REDIS_HASH_USER_VIEWED_USERS_KEY, $webUserId), $userId)) {
                                $Redis->sAdd(sprintf(EncountersBundle::REDIS_SET_USER_CONTACTS_QUEUE_KEY, $webUserId), $userId) && $usersAddedCount++;
                            }
                        }
                    }
                }
            }

            $this->log("[Contacts queue for user_id=<info>$webUserId</info>] <error>$usersAddedCount</error> users were added;");

            $Redis->hSet(
                sprintf(EncountersBundle::REDIS_HASH_USER_CRON_DETAILS_KEY, $webUserId),
                EncountersBundle::REDIS_HASH_KEY_CONTACTS_QUEUE_UPDATED,
                time()
            );
        } else {
            throw new CronScriptException("Could not fetch contact list for user_id=$webUserId");
        }
    }
}