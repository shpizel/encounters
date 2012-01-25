<?php
namespace Mamba\PlatformBundle\API;

/**
 * Contacts
 *
 * @package PlatformBundle
 */
class Contacts {

    /**
     * Получение списка папок «моих сообщений» со счетчиками контактов
     *
     * @throws ContactsException, MambaException
     * @return array
     */
    public function getFolderList() {
        $dataArray = Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__);
        return $dataArray;
    }

    /**
     * Получение списка контактов из заданной папки
     *
     * @throws ContactsException, MambaException
     * @return array
     */
    public function getFolderContactList() {
        /**
         * sid
        folder_id
        online
        limit
        offset
        blocks
        ids_only
         */

        $arguments = array();
        $dataArray = Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
        return $dataArray;
    }

    /**
     * Получение списка контактов по заданому лимиту
     *
     * @throws ContactsException, MambaException
     * @return array
     */
    public function getContactList() {
        /**
         * sid
        limit
        online
        blocks
        ids_only
         */

        $arguments = array();
        $dataArray = Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
        return $dataArray;
    }

    /**
     * Написать сообщение в мессенджер от имени пользователя
     *
     * @throws ContactsException, MambaException
     * @return array
     */
    public function sendMessage() {
        /**
         * oid
        message
        extra_params
         */

        $arguments = array();
        $dataArray = Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
        return $dataArray;
    }
}

/**
 * ContactsException
 *
 * @package PlatformBundle
 */
class ContactsException extends \Exception {

}