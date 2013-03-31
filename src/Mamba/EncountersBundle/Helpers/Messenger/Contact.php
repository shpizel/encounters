<?php
namespace Mamba\EncountersBundle\Helpers\Messenger;

/**
 * Class Contact
 *
 * @package Mamba\EncountersBundle\Helpers\Messenger
 */
class Contact {

    private

        /**
         * Contact id
         *
         * @var int
         */
        $id,

        /**
         * Sender id
         *
         * @var
         */
        $senderId,

        /**
         * Reciever id
         *
         * @var int
         */
        $recieverId,

        /**
         * Unread count
         *
         * @var int
         */
        $unreadCount = 0,

        /**
         * Messages count
         *
         * @var int
         */
        $messagesCount = 0,

        /**
         * Blocked contact label
         *
         * @var bool
         */
        $blocked = false,

        /**
         * Changed timestamp
         *
         * @var int
         */
        $changed = 0
    ;

    /**
     * Contact id getter
     *
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Contact id setter
     *
     * @param int $id
     * @return $this
     * @throws ContactException
     */
    public function setId($id) {
        if (!is_int($id)) {
            throw new ContactException("Invalid id type: ". gettype($id));
        }

        $this->id = $id;
        return $this;
    }

    /**
     * Sender id getter
     *
     * @return int
     */
    public function getSenderId() {
        return $this->senderId;
    }

    /**
     * Sender id setter
     *
     * @param int $id
     * @return $this
     * @throws ContactException
     */
    public function setSenderId($id) {
        if (!is_int($id)) {
            throw new ContactException("Invalid sender id type: ". gettype($id));
        }

        $this->senderId = $id;
        return $this;
    }

    /**
     * Reciever id getter
     *
     * @return int
     */
    public function getRecieverId() {
        return $this->recieverId;
    }

    /**
     * Reciever id setter
     *
     * @param int $id
     * @return $this
     * @throws ContactException
     */
    public function setRecieverId($id) {
        if (!is_int($id)) {
            throw new ContactException("Invalid reciever id type: ". gettype($id));
        }

        $this->recieverId = $id;
        return $this;
    }

    /**
     * Unread count getter
     *
     * @return int
     */
    public function getUnreadCount() {
        return $this->unreadCount;
    }

    /**
     * Unread count setter
     *
     * @param int $unreadCount
     * @return $this
     * @throws ContactException
     */
    public function setUnreadCount($unreadCount) {
        if (!is_int($unreadCount)) {
            throw new ContactException("Invalid unread count type: ". gettype($unreadCount));
        }

        $this->unreadCount = $unreadCount;
        return $this;
    }

    /**
     * Blocked getter
     *
     * @return bool
     */
    public function isBlocked() {
        return $this->blocked;
    }

    /**
     * Blocked setter
     *
     * @param bool $isBlocked
     * @return $this
     * @throws ContactException
     */
    public function setBlocked($isBlocked) {
        if (!is_bool($isBlocked)) {
            throw new ContactException("Invalid blocked type: ". gettype($isBlocked));
        }

        $this->blocked = $isBlocked;
        return $this;
    }

    /**
     * Changed timestamp getter
     *
     * @return int
     */
    public function getChanged() {
        return $this->changed;
    }

    /**
     * Changed timestamp setter
     *
     * @param int $changed
     * @return $this
     * @throws ContactException
     */
    public function setChanged($changed) {
        if (!is_int($changed)) {
            throw new ContactException("Invalid changed type: ". gettype($changed));
        }

        $this->changed = $changed;
        return $this;
    }

    /**
     * Messages count getter
     *
     * @return int
     */
    public function getMessagesCount() {
        return $this->unreadCount;
    }

    /**
     * Messages count setter
     *
     * @param int $messagesCount
     * @return $this
     * @throws ContactException
     */
    public function setMessagesCount($messagesCount) {
        if (!is_int($messagesCount)) {
            throw new ContactException("Invalid messages count type: ". gettype($messagesCount));
        }

        $this->messagesCount = $messagesCount;
        return $this;
    }

    /**
     * Array export function
     *
     * @return array
     */
    public function toArray() {
        return
            array(
                'contact_id'     => $this->getId(),
                'sender_id'      => $this->getSenderId(),
                'reciever_id'    => $this->getRecieverId(),
                'messages_count' => $this->getMessagesCount(),
                'unread_count'   => $this->getUnreadCount(),
                'blocked'        => $this->isBlocked(),
                'changed'        => $this->getChanged(),
            )
        ;
    }
}

/**
 * Class ContactException
 *
 * @package Mamba\EncountersBundle\Helpers\Messenger
 */
class ContactException extends \Exception {

}