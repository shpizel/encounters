<?php
namespace Core\MambaBundle\API;

/**
 * Diary
 *
 * @package MambaBundle
 */
class Diary {

    /**
     * Получение списка постов дневника — заголовки и ссылки на посты
     * Выдача — максимум 10 элементов
     *
     * @param int $oid
     * @param int $offset
     * @throws DiaryException, MambaException
     * @return mixed
     */
    public function getPosts($oid, $offset) {
        if (!is_int($oid)) {
            throw new DiaryException("Invalid oid: int expected");
        }

        if (!is_int($offset)) {
            throw new DiaryException("Invalid offset: int expected");
        }

        $arguments = array(
            'oid'    => $oid,
            'offset' => $offset,
        );

        return Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
    }
}

/**
 * DiaryException
 *
 * @package MambaBundle
 */
class DiaryException extends \Exception {

}