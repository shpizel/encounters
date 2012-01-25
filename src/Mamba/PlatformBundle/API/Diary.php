<?php
namespace Mamba\PlatformBundle\API;

/**
 * Diary
 *
 * @package PlatformBundle
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

        $dataArray = Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);

        if (isset($dataArray['posts'])) {
            return $dataArray['posts'];
        }

        throw new DiaryException("'posts' field was not found");
    }
}

/**
 * DiaryException
 *
 * @package PlatformBundle
 */
class DiaryException extends \Exception {

}