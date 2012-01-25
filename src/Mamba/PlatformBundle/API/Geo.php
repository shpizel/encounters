<?php
namespace Mamba\PlatformBundle\API;

/**
 * Geo
 *
 * @package PlatformBundle
 */
class Geo {

    /**
     * Получение списка стран
     *
     * @throws MambaException
     * @return array
     */
    public function getCountries() {
        $dataArray = Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__);
        return $dataArray;
    }

    /**
     * Получение списка регионов страны
     *
     * @param int $countryId
     * @throws GeoException, MambaException
     * @return array
     */
    public function getRegions($countryId) {
        if (!is_int($countryId)) {
            throw new GeoException('Invalid country id type ' . gettype($countryId));
        }

        $arguments = array(
            'country_id' => $countryId,
        );

        $dataArray = Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
        return $dataArray;
    }

    /**
     * Получение списка городов региона
     *
     * @param int $regionId
     * @throws GeoException, MambaException
     * @return array
     */
    public function getCities($regionId) {
        if (!is_int($regionId)) {
            throw new GeoException('Invalid region id type ' . gettype($regionId));
        }

        $arguments = array(
            'region_id' => $regionId,
        );

        $dataArray = Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
        return $dataArray;
    }

    /**
     * Получение списка станций метро города
     *
     * @param int $cityId
     * @throws GeoException, MambaException
     * @return array
     */
    public function getMetro($cityId) {
        if (!is_int($cityId)) {
            throw new GeoException('Invalid city id type ' . gettype($cityId));
        }

        $arguments = array(
            'city_id' => $cityId,
        );

        $dataArray = Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
        return $dataArray;
    }
}

/**
 * GeoException
 *
 * @package PlatformBundle
 */
class GeoException extends \Exception {

}