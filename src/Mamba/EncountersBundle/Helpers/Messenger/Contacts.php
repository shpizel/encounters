<?php
namespace Mamba\EncountersBundle\Helpers\Messenger;

use Mamba\EncountersBundle\Helpers\Helper;
use PDO;

/**
 * Class Contacts
 *
 * @package Mamba\EncountersBundle\Helpers\Messenger
 */
class Contacts extends Helper {

    const

        /**
         * SQL-запрос на получение контакта по айдишникам участвующих пользователей
         *
         * @var str
         */
        SQL_GET_CONTACT = "
            SELECT
                *
            FROM
                `Messenger`.`Contacts`
            WHERE
                `sender_id`   = :web_user_id AND
                `reciever_id` = :current_user_id
            LIMIT
                1",

        /**
         * SQL-запрос на получение контакта по его идентификатору
         *
         * @var str
         */
        SQL_GET_CONTACT_BY_ID = "
            SELECT
                *
            FROM
                `Messenger`.`Contacts`
            WHERE
                `contact_id` = :contact_id
            LIMIT
                1",

        /**
         * SQL-запрос на создание контакта
         *
         * @var str
         */
        SQL_CREATE_CONTACT = "
            INSERT INTO
                `Messenger`.`Contacts`
            SET
                `sender_id` = :sender_id,
                `reciever_id` = :reciever_id,
                `messages_count` = :messages_count,
                `unread_count` = :unread_count,
                `blocked` = :blocked,
                `changed` = :changed",

        /**
         * SQL-запрос на обновление контакта
         *
         * @var str
         */
        SQL_UPDATE_CONTACT = "
            UPDATE
                `Messenger`.`Contacts`
            SET
                `sender_id` = :sender_id,
                `reciever_id` = :reciever_id,
                `messages_count` = :messages_count,
                `unread_count` = :unread_count,
                `blocked` = :blocked,
                `changed` = :changed
            WHERE
                `contact_id` = :id",

        /**
         * SQL-запрос на получение контактов пользователей
         *
         * @var str
         */
        SQL_GET_CONTACTS = "
            SELECT
                *
            FROM
                `Messenger`.`Contacts`
            WHERE
                `sender_id` = :user_id
            ORDER BY
                `changed` DESC"
    ;

    /**
     * Contact getter
     *
     * @param int $webUserId
     * @param int $currentUserId
     * @return Contact|null
     * @throws ContactsException
     */
    public function getContact($webUserId, $currentUserId) {
        if (!is_int($webUserId)) {
            throw new ContactsException("Invalid web user id type: ". gettype($webUserId));
        } elseif (!is_int($currentUserId)) {
            throw new ContactsException("Invalid current user id type: ". gettype($currentUserId));
        }

        $stmt = $this->getDoctrine()
            ->getEntityManager()
            ->getConnection()
            ->prepare(self::SQL_GET_CONTACT)
        ;

        $_webUserId = $webUserId;
        $_currentUserId = $currentUserId;

        $stmt->bindParam('web_user_id', $_webUserId, PDO::PARAM_INT);
        $stmt->bindParam('current_user_id', $_currentUserId, PDO::PARAM_INT);

        if ($result = $stmt->execute()) {
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return
                    (new Contact)
                        ->setId((int) $row['contact_id'])
                        ->setSenderId((int) $row['sender_id'])
                        ->setRecieverId((int) $row['reciever_id'])
                        ->setUnreadCount((int) $row['unread_count'])
                        ->setBlocked($row['blocked'] == 'Y' ? true : false)
                        ->setChanged((int) $row['changed'])
                ;
            }
        }
    }

    /**
     * Contact getter by id
     *
     * @param int $contactId
     * @return Contact|null
     * @throws ContactsException
     */
    public function getContactById($contactId) {
        if (!is_int($contactId)) {
            throw new ContactsException("Invalid contact id type: ". gettype($contactId));
        }

        $stmt = $this->getDoctrine()
            ->getEntityManager()
            ->getConnection()
            ->prepare(self::SQL_GET_CONTACT_BY_ID)
        ;

        $_contactId = $contactId;

        $stmt->bindParam('contact_id', $_contactId, PDO::PARAM_INT);

        if ($result = $stmt->execute()) {
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return
                    (new Contact)
                        ->setId((int) $row['contact_id'])
                        ->setSenderId((int) $row['sender_id'])
                        ->setRecieverId((int) $row['reciever_id'])
                        ->setUnreadCount((int) $row['unread_count'])
                        ->setBlocked($row['blocked'] == 'Y' ? true : false)
                        ->setChanged((int) $row['changed'])
                    ;
            }
        }
    }

    /**
     * Contact creator
     *
     * @param int $webUserId
     * @param int $currentUserId
     * @return Contact|null
     * @throws ContactsException
     */
    public function createContact($webUserId, $currentUserId) {
        $Contact = (new Contact)
            ->setSenderId($webUserId)
            ->setRecieverId($currentUserId)
            ->setMessagesCount(0)
            ->setUnreadCount(0)
            ->setBlocked(false)
            ->setChanged(time())
        ;

        $Connection = $this->getDoctrine()->getEntityManager()->getConnection();
        $stmt = $Connection->prepare(self::SQL_CREATE_CONTACT);

        $senderId = $Contact->getSenderId();
        $recieverId = $Contact->getRecieverId();
        $messagesCount = $Contact->getMessagesCount();
        $unreadCount = $Contact->getUnreadCount();
        $isBlocked = $Contact->isBlocked() ? 'Y' : 'N';
        $changed = $Contact->getChanged();

        $stmt->bindParam('sender_id',  $senderId, PDO::PARAM_INT);
        $stmt->bindParam('reciever_id', $recieverId, PDO::PARAM_INT);
        $stmt->bindParam('messages_count', $messagesCount, PDO::PARAM_INT);
        $stmt->bindParam('unread_count', $unreadCount, PDO::PARAM_INT);
        $stmt->bindParam('blocked', $isBlocked, PDO::PARAM_STR);
        $stmt->bindParam('changed', $changed, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return
                $Contact
                    ->setId((int)$Connection->lastInsertId())
            ;
        }
    }

    /**
     * Contact updater
     *
     * @param Contact $Contact
     * @return
     */
    public function updateContact(Contact $Contact) {
        $contactId = $Contact->getId();
        $senderId = $Contact->getSenderId();
        $recieverId = $Contact->getRecieverId();
        $messagesCount = $Contact->getMessagesCount();
        $unreadCount = $Contact->getUnreadCount();
        $isBlocked = $Contact->isBlocked() ? 'Y' : 'N';
        $changed = $Contact->getChanged();

        return
            $this->getDoctrine()
                ->getEntityManager()
                ->getConnection()
                ->prepare(self::SQL_UPDATE_CONTACT)
                ->execute(
                    array(
                        `contact_id` => $contactId,
                        `sender_id` => $senderId,
                        `reciever_id` => $recieverId,
                        `messages_count` => $messagesCount,
                        `unread_count` => $unreadCount,
                        `blocked` => $isBlocked,
                        `changed` => $changed,
                    )
                )
        ;
    }

    /**
     * Contacts getter
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array|null
     */
    public function getContacts($userId, $limit = 10, $offset = 0) {
        if (!is_int($userId)) {
            throw new ContactsException("Invalid user id type: ". gettype($userId));
        } elseif (!is_int($limit)) {
            throw new ContactsException("Invalid limit type: ". gettype($limit));
        } elseif (!is_int($offset)) {
            throw new ContactsException("Invalid offset type: ". gettype($offset));
        }

        $stmt = $this->getDoctrine()
            ->getEntityManager()
            ->getConnection()
            ->prepare(
                self::SQL_GET_CONTACTS .
                    " LIMIT {$limit} " .
                    " OFFSET {$offset}"
            )
        ;

        $_userId = $userId;
        $stmt->bindParam('user_id', $_userId, PDO::PARAM_INT);

        if ($result = $stmt->execute()) {
            $return = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $return[] =
                    (new Contact)
                        ->setId((int) $row['contact_id'])
                        ->setSenderId((int) $row['sender_id'])
                        ->setRecieverId((int) $row['reciever_id'])
                        ->setMessagesCount((int) $row['messages_count'])
                        ->setUnreadCount((int) $row['unread_count'])
                        ->setBlocked($row['blocked'] == 'Y' ? true : false)
                        ->setChanged((int) $row['changed'])
                ;
            }

            return $return ?: null;
        }
    }
}

/**
 * Class ContactsException
 *
 * @package Mamba\EncountersBundle\Helpers\Messenger
 */
class ContactsException extends \Exception {

}