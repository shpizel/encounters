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

        if (isset($dataArray['folders'])) {
            return $dataArray['folders'];
        }

        throw new ContactsException("'folders' field was not found");
    }

    /**
     * Получение списка контактов из заданной папки
     *
     * @param int $folderId По умолчанию общая папка
     * @param bool $onlyOnline по умолчанию - все вместе, если указан -то только те, что онлайн
     * @param int $limit лимит запрашиваемых данных (не больше 100, default = 100)
     * @param int $offset по умолчанию = 0
     * @param array $blocks = array("about", "location", "flags", "familiarity", "type", "favour", "other")
     * @param bool $onlyIds если параметр установлен (=1) - метод возвращает только id анкет, если же нет - то обычное поведение метода"
     * @throws ContactsException, MambaException
     * @return array
     */
    public function getFolderContactList(
            $folderId = null,
            $onlyOnline = false,
            $limit = 100,
            $offset = 0,
            array $blocks = array("about", "location", "flags", "familiarity", "type", "favour", "other"),
            $onlyIds = null
    ) {
        $arguments = array();
        if ($folderId) {
            if (is_int($folderId)) {
                $arguments['folder_id'] = $folderId;
            } else {
                throw new ContactsException("Invalid folderId type: " . gettype($folderId));
            }
        }

        if ($onlyOnline) {
            $arguments['online'] = true;
        }

        if ($limit > 100 || !$limit || $limit < 0 || !is_int($limit)) {
            throw new ContactsException("Invalid limit: " . $limit);
        } else {
            $arguments['limit'] = $limit;
        }

        if ($offset) {
            if (!is_int($offset)) {
                throw new ContactsException("Invalid offset type: " . gettype($offset));
            }

            $arguments['offset'] = $offset;
        }

        $availableBlocks = array("about", "location", "flags", "familiarity", "type", "favour", "other");
        foreach ($blocks as &$block) {
            $block = strtolower($block);
            if (!in_array($block, $availableBlocks)) {
                throw new ContactsException("Invalid block type: " . gettype($block));
            }
        }

        $arguments['blocks'] = implode(",", $blocks);

        if ($onlyIds) {
            $arguments['only_ids'] = true;
        }

        $dataArray = Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);

        if (isset($dataArray['contacts'])) {
            return $dataArray['contacts'];
        }

        throw new ContactsException("'contacts' field was not found");
    }

    /**
     * Получение списка контактов по заданому лимиту
     *
     * @param int $limit число получаемых контактов. не больше 100. по умолчанию limit приравнивается к 100
     * @param bool $onlyOnline по умолчанию - все вместе, если указан -то только те, что онлайн
     * @param array $blocks = array("about", "location", "flags", "familiarity", "type", "favour", "other")
     * @param bool $onlyIds если параметр установлен (=1) - метод возвращает только id анкет, если же нет - то обычное поведение метода"
     * @throws ContactsException, MambaException
     * @return array
     */
    public function getContactList(
        $limit = 100,
        $onlyOnline = false,
        array $blocks = array("about", "location", "flags", "familiarity", "type", "favour", "other"),
        $onlyIds = null
    ) {
        $arguments = array();

        if ($limit > 100 || !$limit || $limit < 0 || !is_int($limit)) {
            throw new ContactsException("Invalid limit: " . $limit);
        } else {
            $arguments['limit'] = $limit;
        }

        if ($onlyOnline) {
            $arguments['online'] = true;
        }

        $availableBlocks = array("about", "location", "flags", "familiarity", "type", "favour", "other");
        foreach ($blocks as &$block) {
            $block = strtolower($block);
            if (!in_array($block, $availableBlocks)) {
                throw new ContactsException("Invalid block type: " . gettype($block));
            }
        }

        $arguments['blocks'] = implode(",", $blocks);

        if ($onlyIds) {
            $arguments['only_ids'] = true;
        }

        $dataArray = Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
        if (isset($dataArray['contacts'])) {
            return $dataArray['contacts'];
        }

        throw new ContactsException("'contacts' field was not found");
    }

    /**
     * Написать сообщение в мессенджер от имени пользователя
     *
     * @param int $oid
     * @param string $message
     * @param string $extraParams строка дополнительных параметров, которые будут переданы в ссылку на приложение, в выводе данных метода. максимальная длина 255 символов.
     * @throws ContactsException, MambaException
     * @return array
     */
    public function sendMessage($oid, $message, $extraParams = null) {
        $arguments = array();

        if (!is_int($oid)) {
            throw new ContactsException("Invalid oid type: " . gettype($oid));
        }

        $arguments['oid'] = $oid;

        if (!is_string($message)) {
            throw new ContactsException("Invalid message type: ". gettype($message));
        }

        $arguments['message'] = $message;

        if ($extraParams) {
            if (!is_string($extraParams)) {
                throw new ContactsException("Invalid message type: ". gettype($extraParams));
            }

            $arguments['extra_params'] = $extraParams;
        }

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