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
        $this->getContainer()->get('gearman')->getClient()->doHighBackground(EncountersBundle::GEARMAN_CONTACTS_QUEUE_UPDATE_FUNCTION_NAME, serialize(array(560015854, 1)));
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
     * Обновляет пользовательскую очередь из поиска
     *
     * @param $job
     */
    public function updateContactsQueue($job) {
        $Mamba = $this->getContainer()->get('mamba');
        if (list($userId, $limit) = unserialize($job->workload())) {
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

        $contactList = $Mamba->Contacts()->getContactList();
        foreach ($contactList as $contact) {
            $contactInfo = $contact['info'];
            list($gender, $age) = array($contactInfo['gender'], $contactInfo['age']);

            if ($gender == $searchPreferences['gender']) {
                if (!$age || ($age >= $searchPreferences['age_from'] && $age <= $searchPreferences['age_to'])) {
                    if (!$Redis->hExists(sprintf(EncountersBundle::REDIS_HASH_USER_VIEWED_USERS_KEY, $Mamba->get('oid')), $userId)) {
                        $Redis->zAdd(sprintf(EncountersBundle::REDIS_ZSET_USER_CONTACTS_QUEUE_KEY, $Mamba->get('oid')), 1, $userId);
                    }
                }
            }
        }

        $Redis->set(sprinft(EncountersBundle::REDIS_USER_LAST_CONTACTS_QUEUE_UPDATED_KEY, $Mamba->get('oid')), time());
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