<?php

namespace Mamba\PlatformBundle\API;

class Notify {

    /**
     * Отослать извещение в мессенджер от имени пользователя «Менеджер приложений»
     *
     * @return array
     */
    public function sendMessage() {
        /**
         * oids
        sid
        message
        extra_params
         */
        $arguments = array();
        $dataArray = Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
        return $dataArray;
    }
}

class NotifyException extends \Exception {

}