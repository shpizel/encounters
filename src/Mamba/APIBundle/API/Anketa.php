<?php

namespace Mamba\APIBundle\API;

class Anketa {

    /**
     * Получение всех полей анкеты
     *
     * @param array $ids (MAX 100 oid анкет или logins)
     * @throws AnketaException, MambaException
     * @return array
     */
    public function getInfo(array $ids, array $blocks = array(), $onlyIds = null) {
        $max = 100;

        if (count($ids) > $max) {
            $ids = array_slice($ids, 0, $max);
        }

        reset($ids);

        if (is_int(current($ids))) {
            $typeOfRequest = 'oids';
        } elseif (is_string(current($ids))) {
            $typeOfRequest = 'logins';
        } else {
            throw new AnketaException('Invalid params');
        }

        foreach ($ids as $id) {
            if ($typeOfRequest == 'oids')  {
                if (!is_int($id)) {
                    throw new AnketaException('Invalid type of param: ' . gettype($id) . ", expected int");
                }
            } elseif ($typeOfRequest == 'logins') {
                if (!is_string($id)) {
                    throw new AnketaException('Invalid type of login: ' . gettype($id) . ", expected string");
                }
            }
        }

        $availableBlocks = array("about", "location", "flags", "familiarity", "type", "favour", "other");
        foreach ($blocks as &$block) {
            $block = strtolower($block);
            if (!in_array($block, $availableBlocks)) {
                throw new AnketaException("Invalid block type: " . $block);
            }
        }

        $arguments = array(
            $typeOfRequest => implode(",", $ids),
        );

        if (!empty($blocks)) {
            $arguments['blocks'] = implode(",", $blocks);
        }

        if ($onlyIds) {
            $arguments['ids_only'] = 1;
        }

        $dataArray = Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
        return $dataArray;
    }

    /**
     * Получение интересов
     *
     * @param int $oid Anketa id
     * @throws AnketaException, MambaException
     * @return array
     */
    public function getInterests($oid) {
        if (!is_int($oid)) {
            throw new AnketaException('Invalid oid: expected int');
        }

        $arguments = array(
            'oid' => $oid,
        );

        $dataArray = Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
        return $dataArray;
    }

    /**
     * Получение объявлений из попутчиков
     *
     * @param int $oid Anketa id
     * @throws AnketaException, MambaException
     * @return array
     */
    public function getTravel($oid) {
        if (!is_int($oid)) {
            throw new PhotosException('Invalid oid: expected int');
        }

        $arguments = array(
            'oid' => $oid,
        );

        $dataArray = Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
        return $dataArray;
    }

    /**
     * Получение списка флагов любой анкеты: VIP, реал, лидер, maketop, интим за деньги
     *
     * @param array $ids (anketa ids)
     * @throws AnketaException, MambaException
     * @return array(anketa_id=>array(data), ..)
     */
    public function getFlags(array $ids) {
        foreach ($ids as $id) {
            if (!is_int($id)) {
                throw new AnketaException('Invalid type of param: ' . gettype($id) . ", expected int");
            }
        }

        $arguments = array(
            'oids' => implode(",", $ids),
        );

        $dataArray = Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
        $result = array();

        foreach ($dataArray as $item) {
            $anketaId = $item['anketa_id'];
            unset($item['anketa_id']);
            $result[$anketaId] = $item;
        }

        return $result;
    }

    /**
     * Статус online или когда был крайний раз на сайте, если не надета шапка-невидимка
     *
     * @param array $ids (anketa ids)
     * @throws AnketaException, MambaException
     * @return array(anketa_id => online, ..)
     */
    public function isOnline(array $ids) {
        foreach ($ids as $id) {
            if (!is_int($id)) {
                throw new AnketaException('Invalid type of param: ' . gettype($id) . ", expected int");
            }
        }

        $arguments = array(
            'oids' => implode(",", $ids),
        );

        $dataArray = Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
        $result = array();

        foreach ($dataArray as $item) {
            $result[$item['anketa_id']] = $item['is_online'] ;
        }

        return $result;
    }

    /**
     * Проверка установлено ли указанное приложение у указанной анкеты
     *
     * @param array $ids (anketa ids)
     * @throws AnketaException, MambaException
     * @return array
     */
    public function isAppUser(array $ids) {
        foreach ($ids as $id) {
            if (!is_int($id)) {
                throw new AnketaException('Invalid type of param: ' . gettype($id) . ", expected int");
            }
        }

        $arguments = array(
            'oids' => implode(",", $ids),
        );

        $dataArray = Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
        return $dataArray;
    }

    /**
     * Проверка, является ли пользователь владельцем приложения
     *
     * @throws AnketaException, MambaException
     * @return array
     */
    public function isAppOwner() {
        $dataArray = Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__);
        return $dataArray;
    }

    /**
     * Проверка, стоит ли приложение в «Избранных» у пользователя
     *
     * @throws AnketaException, MambaException
     * @return array
     */
    public function inFavourites() {
        $dataArray = Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__);
        return $dataArray;
    }
}

class AnketaException extends \Exception {

}