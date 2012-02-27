<?php
namespace Mamba\PlatformBundle\API;

/**
 * Anketa
 *
 * @package PlatformBundle
 */
class Anketa {

    /**
     * Получение всех полей анкеты
     *
     * @param array|int|str|string_array $ids (anketa ids) max 100
     * @param array blocks = ("about", "location", "flags", "familiarity", "type", "favour", "other")
     * @throws AnketaException, MambaException
     * @return array
     */
    public function getInfo($ids, array $blocks = array("about", "location", "flags", "familiarity", "type", "favour", "other")) {
        if (!is_array($ids)) {
            if (is_string($ids)) {
                $ids = str_replace(" ", "", $ids);
                $ids = explode(",", $ids);
            } elseif (is_int($ids)) {
                $ids = array($ids);
            } else {
                throw new AnketaException("Invalid ids param");
            }

            $ids = array_filter($ids, function($id) {
                return (bool) $id;
            });

            return $this->getInfo($ids, $blocks);
        }

        $max = 100;
        if (count($ids) > $max) {
            throw new AnketaException("Maximum ids array size exceed: " . count($ids));
        }

        reset($ids);

        if (is_int(current($ids)) || is_numeric(current($ids))) {
            $typeOfRequest = 'oids';
        } elseif (is_string(current($ids))) {
            $typeOfRequest = 'logins';
        } else {
            throw new AnketaException('Invalid params');
        }

        foreach ($ids as &$id) {
            if ($typeOfRequest == 'oids')  {
                if (!is_int($id)) {
                    if (is_numeric($id)) {
                        $id = intval($id);
                    } else {
                        throw new AnketaException('Invalid type of param: ' . gettype($id) . ", expected int");
                    }
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
                throw new AnketaException("Invalid block type: " . gettype($block));
            }
        }

        $arguments = array(
            $typeOfRequest => implode(",", $ids),
        );

        if (!empty($blocks)) {
            $arguments['blocks'] = implode(",", $blocks);
        }

        return Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
    }

    /**
     * Получение интересов
     *
     * @param int|str $oid anketa_id
     * @throws AnketaException, MambaException
     * @return array
     */
    public function getInterests($oid) {
        if (!is_int($oid)) {
            if (is_string($oid) && is_numeric($oid)) {
                $oid = (int) $oid;
            } else {
                throw new AnketaException('Invalid oid');
            }
        }

        $arguments = array(
            'oid' => $oid,
        );

        return Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
    }

    /**
     * Получение объявлений из попутчиков
     *
     * @param int $oid anketa_id
     * @throws AnketaException, MambaException
     * @return array
     */
    public function getTravel($oid) {
        if (!is_int($oid)) {
            if (is_string($oid) && is_numeric($oid)) {
                $oid = (int) $oid;
            } else {
                throw new AnketaException('Invalid oid');
            }
        }

        $arguments = array(
            'oid' => $oid,
        );

        return Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
    }

    /**
     * Получение списка флагов любой анкеты: VIP, реал, лидер, maketop, интим за деньги
     *
     * @param array|int|str|string_array $ids (anketa ids)
     * @throws AnketaException, MambaException
     * @return array
     */
    public function getFlags($ids) {
        if (!is_array($ids)) {
            if (is_string($ids)) {
                $ids = str_replace(" ", "", $ids);
                $ids = explode(",", $ids);
            } elseif (is_int($ids)) {
                $ids = array($ids);
            } else {
                throw new AnketaException("Invalid ids param");
            }

            $ids = array_filter($ids, function($id) {
                return (bool) $id;
            });

            return $this->getFlags($ids);
        }

        $max = 30;
        if (count($ids) > $max) {
            throw new AnketaException("Maximum ids array size exceed: " . count($ids));
        }

        foreach ($ids as &$id) {
            if (!is_int($id)) {
                if (is_numeric($id)) {
                    $id = (int) $id;
                } else {
                    throw new AnketaException("Invalid id type: ". gettype($id));
                }
            }
        }

        $arguments = array(
            'oids' => implode(",", $ids),
        );

        return Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
    }

    /**
     * Статус online или когда был крайний раз на сайте, если не надета шапка-невидимка
     *
     * @param array|int|str|string_array $ids (anketa ids)
     * @throws AnketaException, MambaException
     * @return array
     */
    public function isOnline($ids) {
        if (!is_array($ids)) {
            if (is_string($ids)) {
                $ids = str_replace(" ", "", $ids);
                $ids = explode(",", $ids);
            } elseif (is_int($ids)) {
                $ids = array($ids);
            } else {
                throw new AnketaException("Invalid ids param");
            }

            $ids = array_filter($ids, function($id) {
                return (bool) $id;
            });

            return $this->isOnline($ids);
        }

        $max = 30;
        if (count($ids) > $max) {
            throw new AnketaException("Maximum ids array size exceed: " . count($ids));
        }

        foreach ($ids as &$id) {
            if (!is_int($id)) {
                if (is_numeric($id)) {
                    $id = (int) $id;
                } else {
                    throw new AnketaException("Invalid id type: ". gettype($id));
                }
            }
        }

        $arguments = array(
            'oids' => implode(",", $ids),
        );

        return Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
    }

    /**
     * Проверка установлено ли указанное приложение у указанной анкеты
     *
     * @param array|int|str|string_array $ids (anketa ids)
     * @throws AnketaException, MambaException
     * @return array
     */
    public function isAppUser($ids) {
        if (!is_array($ids)) {
            if (is_string($ids)) {
                $ids = str_replace(" ", "", $ids);
                $ids = explode(",", $ids);
            } elseif (is_int($ids)) {
                $ids = array($ids);
            } else {
                throw new AnketaException("Invalid ids param");
            }

            $ids = array_filter($ids, function($id) {
                return (bool) $id;
            });

            return $this->isAppUser($ids);
        }

        $max = 30;
        if (count($ids) > $max) {
            throw new AnketaException("Maximum ids array size exceed: " . count($ids));
        }

        foreach ($ids as &$id) {
            if (!is_int($id)) {
                if (is_numeric($id)) {
                    $id = (int) $id;
                } else {
                    throw new AnketaException("Invalid id type: ". gettype($id));
                }
            }
        }

        $arguments = array(
            'oids' => implode(",", $ids),
        );

        return Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
    }

    /**
     * Проверка, является ли пользователь владельцем приложения
     *
     * @throws AnketaException, MambaException
     * @return array
     */
    public function isAppOwner() {
        return Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__);
    }

    /**
     * Получение хитлиста
     *
     * @param int $period = (-1, -7, -30)
     * @param int $limit
     * @param int $offset
     * @param array blocks = ("about", "location", "flags", "familiarity", "type", "favour", "other")
     * @param bool onlyIds
     * @throws AnketaException, MambaException
     * @return array
     */
    public function getHitlist($period = -1, $limit = null, $offset = null, array $blocks = array("about", "location", "flags", "familiarity", "type", "favour", "other"), $onlyIds = null) {
        $arguments = array();

        if ($period && !in_array($period, array(-1, -7, -30))) {
            throw new AnketaException("Invalid period");
        }

        if ($period) {
            $arguments['period'] = $period;
        }

        if ($limit && !is_int($limit)) {
            throw new AnketaException("Invalid limit type: " . gettype($limit));
        }

        if ($limit) {
            $arguments['limit'] = $limit;
        }

        if ($offset && !is_int($offset)) {
            throw new AnketaException("Invalid offset type: " . gettype($offset));
        }

        $availableBlocks = array("about", "location", "flags", "familiarity", "type", "favour", "other");
        foreach ($blocks as &$block) {
            $block = strtolower($block);
            if (!in_array($block, $availableBlocks)) {
                throw new AnketaException("Invalid block type: " . gettype($block));
            }
        }

        if (!empty($blocks)) {
            $arguments['blocks'] = implode(",", $blocks);
        }

        if ($onlyIds) {
            $arguments['ids_only'] = 1;
        }

        return Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
    }

    /**
     * Проверка, стоит ли приложение в «Избранных» у пользователя
     *
     * @throws AnketaException, MambaException
     * @return array
     */
    public function inFavourites() {
        return Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__);
    }
}

/**
 * AnketaException
 *
 * @package PlatformBundle
 */
class AnketaException extends \Exception {

}