<?php

namespace Mamba\PlatformBundle\API;

class Contacts {

    /**
     * Получение списка папок «моих сообщений» со счетчиками контактов
     *
     * @return array
     */
    public function getFolderList() {
        $dataArray = Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__);
        return $dataArray;
    }

    /**
     * Получение списка контактов из заданной папки
     *
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

class ContactsException extends \Exception {

}