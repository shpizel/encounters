<?php
namespace Mamba\EncountersBundle\Command;

use Mamba\EncountersBundle\Controller\MessengerController;
use Mamba\EncountersBundle\Script\Script;
use Mamba\EncountersBundle\Tools\Gifts\Gifts;
use Mamba\EncountersBundle\Helpers\Messenger\Message;

/**
 * AACommand
 *
 * @package EncountersBundle
 */
class AACommand extends Script {

    const

        /**
         * Описание скрипта
         *
         * @var str
         */
        SCRIPT_DESCRIPTION = "AA script",

        /**
         * Имя скрипта
         *
         * @var str
         */
        SCRIPT_NAME = "AA"
    ;

    /**
     * Processor
     *
     * @return null
     */
    protected function process() {
        $ContactsHelper = $this->getContactsHelper();
        $MessagesHelper = $this->getMessagesHelper();

        $Contact = $ContactsHelper->getContact(560015854, 1116623183, true);

        $Message = (new Message)
            ->setContactId($Contact->getId())
            ->setType('text')
            ->setDirection('inbox')
            ->setMessage("test")
            ->setTimestamp(time())
        ;


        $MessagesHelper->addMessage($Message);

        $Contact
            ->setChanged(time())
            ->setInboxCount($Contact->getInboxCount() + 1)
            ->setUnreadCount($Contact->getUnreadCount() + 1)
        ;

        $ContactsHelper->updateContact($Contact);

        $this->getCountersHelper()->incr($Contact->getSenderId(), 'messages_unread');
    }
}