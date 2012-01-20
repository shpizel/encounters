<?php

namespace Mamba\PlatformBundle\API;

class Photos {

    /**
     * Получение списка включенных альбомов
     *
     * @param int $oid anketa_id
     * @throws PhotosException, MambaException
     * @return array
     */
    public function getAlbums($oid) {
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
     * Получение списка фотографий для заданного включенного альбома
     *
     * @param int $oid anketa_id
     * @param int $albumId
     * @throws PhotosException, MambaException
     * @return array
     */
    public function get($oid, $albumId = null) {
        if (!is_int($oid)) {
            throw new PhotosException('Invalid oid: expected int');
        }

        if (!is_null($albumId) && !is_int($oid)) {
            throw new PhotosException('Invalid album id: expected int');
        }

        $arguments = array(
            'oid' => $oid,
        );

        if ($albumId) {
            $arguments['album_id'] = $albumId;
        }

        $dataArray = Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
        return $dataArray;
    }
}

class PhotosException extends \Exception {

}