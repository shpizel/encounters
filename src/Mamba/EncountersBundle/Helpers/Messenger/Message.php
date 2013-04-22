<?php
namespace Mamba\EncountersBundle\Helpers\Messenger;

/**
 * Class Message
 *
 * @package Mamba\EncountersBundle\Helpers\Messenger
 */
class Message {
    
    private

        /**
         * Message id
         * 
         * @var int
         */
        $id,

        /**
         * Contact id
         * 
         * @var int
         */
        $contactId,

        /**
         * Message direction
         *
         * @var string
         */
        $direction,

        /**
         * Message type
         *
         * @var string
         */
        $type,

        /**
         * Message body
         * 
         * @var str|array
         */
        $message,

        /**
         * Timestamp
         * 
         * @var int
         */
        $timestamp
    ;

    /**
     * Message id getter
     *
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Message id setter
     *
     * @param int $id
     * @return $this
     * @throws MessageException
     */
    public function setId($id) {
        if (!is_int($id)) {
            throw new MessageException("Invalid id type: ". gettype($id));
        }

        $this->id = $id;
        return $this;
    }

    /**
     * Contact id getter
     *
     * @return int
     */
    public function getContactId() {
        return $this->contactId;
    }

    /**
     * Contact id setter
     *
     * @param int $id
     * @return $this
     * @throws MessageException
     */
    public function setContactId($id) {
        if (!is_int($id)) {
            throw new MessageException("Invalid contact id type: ". gettype($id));
        }

        $this->contactId = $id;
        return $this;
    }

    /**
     * Timestamp getter
     *
     * @return int
     */
    public function getTimestamp() {
        return $this->timestamp;
    }

    /**
     * Timestamp setter
     *
     * @param int $timestamp
     * @return $this
     * @throws MessageException
     */
    public function setTimestamp($timestamp) {
        if (!is_int($timestamp)) {
            throw new MessageException("Invalid changed type: ". gettype($timestamp));
        }

        $this->timestamp = $timestamp;
        return $this;
    }

    /**
     * Message getter
     *
     * @return int
     */
    public function getMessage() {
        return $this->message;
    }

    /**
     * Message setter
     *
     * @param str $message
     * @return $this
     * @throws MessageException
     */
    public function setMessage($message) {
        if (!is_string($message) && !is_array($message)) {
            throw new MessageException("Invalid message type: ". gettype($message));
        }

        $this->message = $message;
        return $this;
    }

    /**
     * Message direction getter
     *
     * @return string
     */
    public function getDirection() {
        return $this->direction;
    }

    /**
     * Message direction setter
     *
     * @param str $direction
     * @return $this
     * @throws MessageException
     */
    public function setDirection($direction) {
        if (!is_string($direction)) {
            throw new MessageException("Invalid direction type: ". gettype($direction));
        } elseif (!in_array($direction, array('inbox', 'outbox'))) {
            throw new MessageException("Invalid direction: ". var_export($direction, true));
        }

        $this->direction = $direction;
        return $this;
    }

    /**
     * Message type getter
     *
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Message type setter
     *
     * @param str $type
     * @return $this
     * @throws MessageException
     */
    public function setType($type) {
        if (!is_string($type)) {
            throw new MessageException("Invalid message type: ". gettype($type));
        } elseif (!in_array($type, array('text', 'gift', 'rating'))) {
            throw new MessageException("Invalid message type: ". var_export($type, true));
        }

        $this->type = $type;
        return $this;
    }

    /**
     * Array exporter
     *
     * @return array
     */
    public function toArray() {
        return
            array(
                'message_id' => $this->getId(),
                'contact_id' => $this->getContactId(),
                'type'       => $this->getType(),
                'direction'  => $this->getDirection(),
                'message'    => $this->getMessage(),
                'timestamp'  => $this->getTimestamp(),
            )
        ;
    }
}

/**
 * Class MessageException
 * 
 * @package Mamba\EncountersBundle\Helpers\Messenger
 */
class MessageException extends \Exception {
    
}