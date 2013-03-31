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
                `contact_id` = :contact_id
            ORDER BY
                `message_id` ASC
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
     * @return array|null
     */
    public function getMessages(Contact $Contact, $limit = 10, $offset = 0) {
        if (!is_int($limit)) {
            throw new MessagesException("Invalid limit type: ". gettype($limit));
        } elseif (!is_int($offset)) {
            throw new MessagesException("Invalid offset type: ". gettype($offset));
        }

        $stmt = $this->getDoctrine()
            ->getEntityManager()
            ->getConnection()
            ->prepare(
                sprintf(
                    self::SQL_GET_MESSAGES,
                    $limit,
                    $offset
                )
            )
        ;

        $_contactId = $Contact->getId();
        $stmt->bindParam('contact_id', $_contactId, PDO::PARAM_INT);

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

            return $return ?: null;
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
            $_message   = $Message->getMessage();
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