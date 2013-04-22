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
                `contact_id`
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

                `inbox_count` = :inbox_count,
                `outbox_count` = :outbox_count,
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
                `inbox_count` = :inbox_count,
                `outbox_count` = :outbox_count,
                `unread_count` = :unread_count,

                `blocked` = :blocked,
                `changed` = :changed
            WHERE
                `contact_id` = :contact_id",

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
                `sender_id` = :sender_id
            ORDER BY
                `changed` DESC
            LIMIT
                %d
            OFFSET
                %d",

        /**
         *
         *
         * @var str
         */
        CONTACT_BY_PARTICIPANTS_CACHE_KEY = "contact_from_%d_to_%d",

        /**
         *
         *
         * @var str
         */
        CONTACT_CACHE_KEY = 'contact_id_%d'
    ;

    /**
     * Contact getter
     *
     * @param int $webUserId
     * @param int $currentUserId
     * @param bool $autoCreate = true
     * @return Contact|null
     * @throws ContactsException
     */
    public function getContact($webUserId, $currentUserId, $autoCreate = false) {
        if (!is_int($webUserId)) {
            throw new ContactsException("Invalid web user id type: ". gettype($webUserId));
        } elseif (!is_int($currentUserId)) {
            throw new ContactsException("Invalid current user id type: ". gettype($currentUserId));
        } elseif (!is_bool($autoCreate)) {
            throw new ContactsException("Invalid autocreate param type: ". gettype($autoCreate));
        }

        /** пытаемся достать из кеша */
        $Memcache = $this->getMemcache();
        $cacheKey = sprintf(self::CONTACT_BY_PARTICIPANTS_CACHE_KEY, $webUserId, $currentUserId);
        if ($contactId = $Memcache->get($cacheKey)) {
            return $this->getContactById((int) $contactId);
        }

        /** в кеше нету - идем в базу */
        $stmt = $this->getDoctrine()
            ->getEntityManager()
            ->getConnection()
            ->prepare(
                self::SQL_GET_CONTACT
            )
        ;

        $_webUserId = $webUserId;
        $_currentUserId = $currentUserId;

        $stmt->bindParam('web_user_id', $_webUserId, PDO::PARAM_INT);
        $stmt->bindParam('current_user_id', $_currentUserId, PDO::PARAM_INT);

        if ($result = $stmt->execute()) {
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $contactId = (int) $row['contact_id'];

                /** пишем кеш навечно */
                $Memcache->set($cacheKey, $contactId);

                return $this->getContactById($contactId);
            }
        }

        if ($autoCreate) {
            return $this->createContact($webUserId, $currentUserId);
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

        $Memcache = $this->getMemcache();
        $cacheKey = sprintf(self::CONTACT_CACHE_KEY, $contactId);

        if ($Contact = $Memcache->get($cacheKey)) {
            return unserialize($Contact);
        }

        $stmt = $this->getDoctrine()
            ->getEntityManager()
            ->getConnection()
            ->prepare(
                self::SQL_GET_CONTACT_BY_ID
            )
        ;

        $_contactId = $contactId;

        $stmt->bindParam('contact_id', $_contactId, PDO::PARAM_INT);

        if ($result = $stmt->execute()) {
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $Contact = (new Contact)
                    ->setId((int) $row['contact_id'])
                    ->setSenderId((int) $row['sender_id'])
                    ->setRecieverId((int) $row['reciever_id'])
                    ->setInboxCount((int) $row['inbox_count'])
                    ->setOutboxCount((int) $row['outbox_count'])
                    ->setUnreadCount((int) $row['unread_count'])
                    ->setBlocked($row['blocked'] == 'Y' ? true : false)
                    ->setChanged((int) $row['changed'])
                ;

                $Memcache->set($cacheKey, serialize($Contact));

                return $Contact;
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
            ->setInboxCount(0)
            ->setOutboxCount(0)
            ->setUnreadCount(0)
            ->setBlocked(false)
            ->setChanged(time())
        ;

        $Connection = $this->getDoctrine()->getEntityManager()->getConnection();
        $stmt = $Connection->prepare(self::SQL_CREATE_CONTACT);

        $senderId = $Contact->getSenderId();
        $recieverId = $Contact->getRecieverId();
        $inboxCount = $Contact->getInboxCount();
        $outboxCount = $Contact->getOutboxCount();
        $unreadCount = $Contact->getUnreadCount();
        $isBlocked = $Contact->isBlocked() ? 'Y' : 'N';
        $changed = $Contact->getChanged();

        $stmt->bindParam('sender_id',  $senderId, PDO::PARAM_INT);
        $stmt->bindParam('reciever_id', $recieverId, PDO::PARAM_INT);
        $stmt->bindParam('inbox_count', $inboxCount, PDO::PARAM_INT);
        $stmt->bindParam('outbox_count', $outboxCount, PDO::PARAM_STR);
        $stmt->bindParam('unread_count', $unreadCount, PDO::PARAM_INT);
        $stmt->bindParam('blocked', $isBlocked, PDO::PARAM_STR);
        $stmt->bindParam('changed', $changed, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $Contact->setId((int)$Connection->lastInsertId());

            $Memcache = $this->getMemcache();
            $cacheKey = sprintf(self::CONTACT_CACHE_KEY, $Contact->getId());
            $Memcache->set($cacheKey, serialize($Contact));

            return $Contact;
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

        $inboxCount = $Contact->getInboxCount();
        $outboxCount = $Contact->getOutboxCount();
        $unreadCount = $Contact->getUnreadCount();

        $isBlocked = $Contact->isBlocked() ? 'Y' : 'N';
        $changed = $Contact->getChanged();

        $stmt = $this->getDoctrine()
            ->getEntityManager()
            ->getConnection()
            ->prepare(
                self::SQL_UPDATE_CONTACT
            )
        ;

        $stmt->bindParam('contact_id', $contactId, PDO::PARAM_INT);
        $stmt->bindParam('inbox_count', $inboxCount, PDO::PARAM_INT);
        $stmt->bindParam('outbox_count', $outboxCount, PDO::PARAM_INT);
        $stmt->bindParam('unread_count', $unreadCount, PDO::PARAM_INT);
        $stmt->bindParam('blocked', $isBlocked, PDO::PARAM_STR);
        $stmt->bindParam('changed', $changed, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $Memcache = $this->getMemcache();
            $cacheKey = sprintf(self::CONTACT_CACHE_KEY, $Contact->getId());
            $Memcache->set($cacheKey, serialize($Contact));

            return $Contact;
        }
    }

    /**
     * Contacts getter
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array|null
     */
    public function getContacts($userId, $limit = 20, $offset = 0) {
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
                sprintf(
                    self::SQL_GET_CONTACTS,
                    $limit,
                    $offset
                )
            )
        ;

        $_senderId = $userId;
        $stmt->bindParam('sender_id', $_senderId, PDO::PARAM_INT);

        if ($result = $stmt->execute()) {
            $return = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $return[] =
                    (new Contact)
                        ->setId((int) $row['contact_id'])
                        ->setSenderId((int) $row['sender_id'])
                        ->setRecieverId((int) $row['reciever_id'])
                        ->setInboxCount((int) $row['inbox_count'])
                        ->setOutboxCount((int) $row['outbox_count'])
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