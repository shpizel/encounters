<?php
namespace Mamba\EncountersBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
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
        SCRIPT_DESCRIPTION = "Contacts queue updater"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $worker = $this->getContainer()->get('gearman')->getWorker();

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_CONTACTS_QUEUE_UPDATE_FUNCTION_NAME, function($job) use($class) {
            try {
                return $class->updateContactsQueue($job);
            } catch (\Exception $e) {
                return;
            }
        });

        while ($worker->work() && $this->iterations) {
            if ($worker->returnCode() != GEARMAN_SUCCESS) {
                break;
            }

            $this->iterations--;
        }
    }

    /**
     * Обновляет пользовательскую очередь из контактов
     *
     * @param $job
     */
    public function updateContactsQueue($job) {
        $Mamba = $this->getContainer()->get('mamba');
        if ($userId = (int)$job->workload()) {
            $Mamba->set('oid', $userId);
            if (!$Mamba->getReady()) {
                return;
            }
        } else {
            return;
        }

        $Redis = $this->getContainer()->get('redis');
        if (!($searchPreferences = $this->getSearchPreferences())) {
            return;
        }

        if ($contactList = $Mamba->Contacts()->getContactList()) {
            foreach ($contactList as $contact) {
                $contactInfo = $contact['info'];
                list($userId, $gender, $age) = array($contactInfo['oid'], $contactInfo['gender'], $contactInfo['age']);

                if (isset($contactInfo['medium_photo_url']) && $contactInfo['medium_photo_url']) {
                    if ($gender == $searchPreferences['gender']) {
                        if (!$age || ($age >= $searchPreferences['age_from'] && $age <= $searchPreferences['age_to'])) {
                            if (!$Redis->hExists(sprintf(EncountersBundle::REDIS_HASH_USER_VIEWED_USERS_KEY, $Mamba->get('oid')), $userId)) {
                                $Redis->zAdd(sprintf(EncountersBundle::REDIS_ZSET_USER_CONTACTS_QUEUE_KEY, $Mamba->get('oid')), 1, $userId);
                            }
                        }
                    }
                }
            }

            $Redis->hSet(
                sprintf(EncountersBundle::REDIS_HASH_USER_CRON_DETAILS_KEY, $Mamba->get('oid')),
                EncountersBundle::REDIS_HASH_KEY_CONTACTS_QUEUE_UPDATED,
                time()
            );
        }
    }

    /**
     * Возвращает поисковые преференции для текущего юзера
     *
     * @return mixed
     */
    private function getSearchPreferences() {
        return
            $this->getContainer()->get('redis')
                ->hGetAll(
                    sprintf(EncountersBundle::REDIS_HASH_USER_SEARCH_PREFERENCES_KEY,
                    $this->getContainer()->get('mamba')->get('oid'))
                )
        ;
    }
}