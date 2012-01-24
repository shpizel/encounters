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
     *
     * @return array
     */
    public function getPosts() {
        $arguments = array();
        $dataArray = Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
        return $dataArray;
    }
}

/**
 * DiaryException
 *
 * @package PlatformBundle
 */
class DiaryException extends \Exception {

}