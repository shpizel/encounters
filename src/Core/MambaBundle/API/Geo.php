<?php
namespace Core\MambaBundle\API;

/**
 * Geo
 *
 * @package MambaBundle
 */
class Geo {

    /**
     * Получение списка стран
     *
     * @throws MambaException
     * @return array
     */
    public function getCountries() {
        return Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__);
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

        return Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
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

        return Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
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

        return Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
    }
}

/**
 * GeoException
 *
 * @package MambaBundle
 */
class GeoException extends \Exception {

}