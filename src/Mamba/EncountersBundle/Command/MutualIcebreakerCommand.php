<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Script\CronScript;

use Mamba\EncountersBundle\EncountersBundle;
use Mamba\EncountersBundle\Helpers\Messenger\Message;

/**
 * MutualIcebreakerCommand
 *
 * @package EncountersBundle
 */
class MutualIcebreakerCommand extends CronScript {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "Mutual icebreaker (send message)",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "cron:messenger:mutual:icebreaker"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $worker = $this->getGearmanWorker();

        $class = $this;
        $worker->addFunction(EncountersBundle::GEARMAN_MUTUAL_ICEBREAKER_FUNCTION_NAME, function($job) use($class) {
            return $class->sendMessage($job);
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
                break;
            } elseif ($worker->returnCode() == GEARMAN_SUCCESS) {
                $this->log("Completed", 64);
            }
        }

        $this->log("Bye", 48);
    }

    /**
     * Обновление таблицы Lastaccess
     *
     * @param $job
     */
    public function sendMessage($job) {
        list($webUserId, $currentUserId) = array_values(unserialize($job->workload()));
        $this->log("Got task for <info>webUserId</info> = {$webUserId}, and <info>currentUserdId</info> = {$currentUserId}");

        $ContactsHelper = $this->getContactsHelper();
        $MessagesHelper = $this->getMessagesHelper();

        if ($ContactsHelper->getContact($currentUserId, $webUserId)) {
            $this->log("Contact already exists", 48);
        } else {
            if ($apiData = $this->getMamba()->Anketa()->getInfo((int) $webUserId)) {
                $userData = $apiData[0];

                if ($userData['info']['gender'] == 'F') {
                    $message = "{$userData['info']['name']}, с которой вы хотели встретиться, ответила, что тоже не против встретиться с вами! Она ждет вашего сообщения, чтобы договориться о встрече!";
                } else {
                    $message = "{$userData['info']['name']}, с которым вы хотели встретиться, ответил, что тоже не против встретиться с вами! Он ждет вашего сообщения, чтобы договориться о встрече!";
                }

                if ($Contact = $ContactsHelper->getContact($currentUserId, $webUserId, true)) {
                    $Message = (new Message)
                        ->setContactId($Contact->getId())
                        ->setType('text')
                        ->setDirection('inbox')
                        ->setMessage($message)
                        ->setTimestamp(time())
                    ;

                    if ($MessagesHelper->addMessage($Message)) {
                        $this->getStatsHelper()->incr('mutual-messages-sent');

                        $Contact
                            ->setChanged(time())
                            ->setInboxCount($Contact->getInboxCount() + 1)
                            ->setUnreadCount($Contact->getUnreadCount() + 1)
                        ;

                        $ContactsHelper->updateContact($Contact);

                        $this->getCountersHelper()->incr($Contact->getSenderId(), 'messages_unread');

                        $this->log($message, 64);
                    } else {
                        $this->log("Could not send message", 16);
                    }
                } else {
                    $this->log("Could not get contact", 16);
                }
            } else {
                $this->log("Could not get user info from platform");
            }
        }
    }
}