<?php
namespace Mamba\EncountersBundle\Helpers\Messenger;

use Mamba\EncountersBundle\Helpers\Helper;
use PDO;

/**
 * Class Messages
 *
 * @package Mamba\EncountersBundle\Helpers\Messenger
 */
class Messages extends Helper {

    const

        /**
         * SQL-запрос на выгрузку сообщений
         *
         * @var str
         */
        SQL_GET_MESSAGES = "
            SELECT
                *
            FROM
                `Messenger`.`Messages`
            WHERE
                `contact_id` = :contact_id AND
                `message_id` %s :last_message_id
            ORDER BY
                `message_id` DESC
            LIMIT
                %d
            OFFSET
                %d",

        /**
         * SQL-запрос на добавление сообщения
         *
         * @var str
         */
        SQL_ADD_MESSAGE = "
            INSERT INTO
                `Messenger`.`Messages`
            SET
                `contact_id` = :contact_id,
                `type` = :type,
                `direction` = :direction,
                `message` = :message,
                `timestamp` = :timestamp"
    ;

    /**
     * Contact messages getter
     *
     * @param Contact $Contact
     * @param int $lastMessageId
     * @param int $limit
     * @param int $offset
     * @return array|null
     */
    public function getMessages(Contact $Contact, $lastMessageId = 0, $direction = 'ASC', $limit = 10, $offset = 0) {
        if (!is_int($limit)) {
            throw new MessagesException("Invalid limit type: ". gettype($limit));
        } elseif (!is_int($offset)) {
            throw new MessagesException("Invalid offset type: ". gettype($offset));
        } elseif (!in_array($direction, ['ASC', 'DESC'])) {
            throw new MessagesException("Invalid direction type: ". var_export($direction, true));
        }

        $stmt = $this->getDoctrine()
            ->getEntityManager()
            ->getConnection()
            ->prepare(
                sprintf(
                    self::SQL_GET_MESSAGES,
                    ($direction == 'ASC') ? '<' : '>',
                    $limit,
                    $offset
                )
            )
        ;

        $_contactId = $Contact->getId();
        $_lastMessageId = $lastMessageId ?: PHP_INT_MAX;
        $stmt->bindParam('contact_id', $_contactId, PDO::PARAM_INT);
        $stmt->bindParam('last_message_id', $_lastMessageId, PDO::PARAM_INT);

        if ($result = $stmt->execute()) {
            $return = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $return[] =
                    (new Message)
                        ->setId((int) $row['message_id'])
                        ->setContactId((int) $row['contact_id'])
                        ->setType($row['type'])
                        ->setDirection($row['direction'])
                        ->setMessage($row['message'])
                        ->setTimestamp((int) $row['timestamp'])
                ;
            }

            return $return ? array_reverse($return) : null;
        }
    }

    /**
     * Message adder
     *
     * @param Message $Message
     * @param bool $delayed
     * @return Message|bool
     */
    public function addMessage(Message $Message, $delayed = false) {
        $Connection = $this->getDoctrine()->getEntityManager()->getConnection();
        $stmt = $Connection->prepare(self::SQL_ADD_MESSAGE);

        if (!$delayed) {
            $_contactId = $Message->getContactId();
            $_type      = $Message->getType();
            $_direction = $Message->getDirection();
            if (is_array($_message   = $Message->getMessage())) {
                $_message = json_encode($_message);
            }

            $_timestamp = $Message->getTimestamp();

            $stmt->bindParam('contact_id',  $_contactId, PDO::PARAM_INT);
            $stmt->bindParam('type', $_type, PDO::PARAM_STR);
            $stmt->bindParam('direction', $_direction, PDO::PARAM_STR);
            $stmt->bindParam('message', $_message, PDO::PARAM_LOB);
            $stmt->bindParam('timestamp', $_timestamp, PDO::PARAM_INT);

            if ($stmt->execute()) {
                return
                    $Message
                        ->setId((int) $Connection->lastInsertId())
                ;
            }
        } else {

            /**
             * Отправляем в очередь
             *
             * @author shpizel
             **/

            return true;
        }
    }
}

/**
 * Class MessagesException
 *
 * @package Mamba\EncountersBundle\Helpers\Messenger
 */
class MessagesException extends \Exception {

}