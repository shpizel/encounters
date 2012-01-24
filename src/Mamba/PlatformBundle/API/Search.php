<?php
namespace Mamba\PlatformBundle\API;

/**
 * Search
 *
 * @package PlatformBundle
 */
class Search {

    /**
     * Стандартный краткий поиск мамбы
     *
     * @param string $whoAmI Кто я? (M, F,MM, FF, MF, N)
     * @param string $lookingFor Кого ищу? (M, F, MM, FF, MF, N)
     * @param int $ageFrom Возраст от
     * @param int $ageTo Возраст до
     * @param array $target Цель знакомства ("friendship", "love", "marriage", "sex", "other")
     * @param bool $onlyWithPhoto Только с фото
     * @param bool $onlyReal Только реал
     * @param bool $onlyWithWebCam Только с вебкой
     * @param bool $noIntim Шлюхи отдыхают
     * @param int $countryId Страна
     * @param int $regionId Регион
     * @param int $cityId Город
     * @param int $metroId Метро
     * @param int $offset Смещение
     * @param array $blocks Блоки анкеты (about, location, flags, familiarity, type, favour,other)
     * @param bool $idsOnly Только айдишники
     * @return array
     */
    public function get(
        $whoAmI         = null,
        $lookingFor     = null,
        $ageFrom        = null,
        $ageTo          = null,
        $target         = null,
        $onlyWithPhoto  = false,
        $onlyReal       = false,
        $onlyWithWebCam = false,
        $noIntim        = false,
        $countryId      = null,
        $regionId       = null,
        $cityId         = null,
        $metroId        = null,
        $offset         = 0,
        array $blocks   = array(),
        $idsOnly        = false
    ) {
        $arguments = array();
        $peopleClasses = array("M", "F", "MM", "FF", "MF", "N");

        if ($whoAmI) {
            $whoAmI = strtoupper($whoAmI);
            if (!in_array($whoAmI, $peopleClasses)) {
                throw new SearchException("Invalid people class: " . $whoAmI);
            }
            $arguments['iam'] = $whoAmI;
        }

        if ($lookingFor) {
            $lookingFor = strtoupper($lookingFor);
            if (!in_array($lookingFor, $peopleClasses)) {
                throw new SearchException("Invalid people class: " . $lookingFor);
            }
            $arguments['look_for'] = $lookingFor;
        }

        if ($ageFrom) {
            if (!is_int($ageFrom)) {
                throw new SearchException("Invalid data type for age: " . gettype($ageFrom));
            }
            $arguments['age_from'] = $ageFrom;
        }

        if ($ageTo) {
            if (!is_int($ageTo)) {
                throw new SearchException("Invalid data type for age: " . gettype($ageTo));
            }
            $arguments['age_to'] = $ageTo;
        }

        if ($target) {
            $target = strtolower($target);
            if (!in_array($target, array("about", "location", "flags", "familiarity", "type", "favour", "other"))) {
                throw new SearchException("Invalid target: " . $target);
            }
            $arguments['target'] = $target;
        }

        $onlyWithPhoto &&
            $arguments['with_photo'] = 1;

        $onlyReal &&
            $arguments['real_only'] = 1;

        $onlyWithWebCam &&
            $arguments['with_web_camera'] = 1;

        $noIntim &&
            $arguments['no_intim'] = 1;

        if ($countryId) {

            if (!is_int($countryId)) {
                throw new SearchException("Invalid data type for country id: " . gettype($countryId));
            }
            $arguments['country_id'] = $countryId;

            if ($regionId) {

                if (!is_int($regionId)) {
                    throw new SearchException("Invalid data type for region id: " . gettype($regionId));
                }
                $arguments['region_id'] = $regionId;

                if ($cityId) {

                    if (!is_int($cityId)) {
                        throw new SearchException("Invalid data type for city id: " . gettype($cityId));
                    }
                    $arguments['city_id'] = $cityId;

                    if ($metroId) {

                        if (!is_int($metroId)) {
                            throw new SearchException("Invalid data type for metro id: " . gettype($metroId));
                        }
                        $arguments['metro_id'] = $metroId;
                    }
                }
            }
        }

        if ($offset) {
            if (!is_int($offset)) {
                throw new SearchException("Invalid data type for offset: " . gettype($offset));
            }
            $arguments['offset'] = $offset;
        }

        if (count($blocks)) {
            $availableBlocks = array("about", "location", "flags", "familiarity", "type", "favour", "other");
            foreach ($blocks as &$block) {
                $block = strtolower($block);
                if (!in_array($block, $availableBlocks)) {
                    throw new AnketaException("Invalid block type: " . $block);
                }
            }

            $arguments['blocks'] = implode(',', $blocks);
        }

        $idsOnly &&
            $arguments['ids_only'] = 1;

        $dataArray = Mamba::remoteExecute(strtolower(__CLASS__) . "." . __FUNCTION__, $arguments);
        return $dataArray;
    }
}

/**
 * SearchException
 *
 * @package PlatformBundle
 */
class SearchException extends \Exception {

}