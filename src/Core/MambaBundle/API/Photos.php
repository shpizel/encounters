<?php
namespace Core\MambaBundle\API;

/**
 * Photos
 *
 * @package MambaBundle
 */
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

        $result = Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
        if (!$result) {
            $result = [
                'albums' => [

                ]
            ];
        }

        return $result;
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

        $result = Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
        if (!$result) {
            $result = [
                'photos' => [

                ]
            ];
        }
        return $result;
    }
}

/**
 * PhotosException
 *
 * @package MambaBundle
 */
class PhotosException extends \Exception {

}