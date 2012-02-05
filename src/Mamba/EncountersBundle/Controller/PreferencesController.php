<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Mamba\PlatformBundle\API\Mamba;
use Mamba\EncountersBundle\EncountersBundle;
use Mamba\EncountersBundle\Preferences;

/**
 * PreferencesController
 *
 * @package EncountersBundle
 */
class PreferencesController extends ApplicationController {

    /**
     * Index action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {
        $Mamba = $this->getMamba();
        if (!$Mamba->getReady()) {
            return $this->redirect($this->generateUrl('welcome'));
        }

        $redisSearchPreferences = $this->getPreferencesObject()->get($Mamba->get('oid'));

        if ($searchPreferences = $this->getSearchPreferencesFromRequest()) {
            $searchPreferences['geo'] = $this->getUserGeoParams($Mamba->get('oid'));
            $this->getPreferencesObject()->set($Mamba->get('oid'), $searchPreferences);

            /**
             * Изменились ли настройки?
             *
             * @author shpizel
             */
            if (!$redisSearchPreferences || array_diff($redisSearchPreferences, $searchPreferences)) {
                $this->cleanUserQueues();
                $this->regenerateUserQueues();
            }

            return $this->redirect($this->generateUrl('welcome'));
        } else {
            $searchPreferences = $redisSearchPreferences;
        }

        if (!$searchPreferences) {
            if ($anketaInfo = $Mamba->Anketa()->getInfo($Mamba->get('oid'))) {
                $anketaInfo = array_shift($anketaInfo);
                if (isset($anketaInfo['info']['gender']) && isset($anketaInfo['familiarity']['lookfor'])) {
                    $lookfor = $anketaInfo['familiarity']['lookfor'];
                    $gender = $anketaInfo['info']['gender'];

                    $searchPreferences = array(
                        'gender'   => $gender == 'F' ? 'M' : 'F',
                    );

                    if (preg_match("!(\d+)\D(\d+)\sлет!i", $lookfor, $ages)) {
                        $searchPreferences['age_to'] = (int) array_pop($ages);
                        $searchPreferences['age_from'] = (int) array_pop($ages);
                    }
                } elseif (isset($anketaInfo['info']['gender']) && isset($anketaInfo['info']['age'])) {
                    list($gender, $age) = array(
                        $anketaInfo['info']['gender'],
                        $anketaInfo['info']['age'],
                    );

                    $searchPreferences = array(
                        'gender'   => $gender == 'F' ? 'M' : 'F',
                        'age_from' => $age - 5,
                        'age_to'   => $age + 5,
                    );

                } else {
                    throw new \LogicException("Could not get search preferences");
                }
            } else {
                throw new \LogicException("Could not get search preferences");
            }
        }

        return $this->render('EncountersBundle:templates:preferences.html.twig', $searchPreferences);
    }

    /**
     * Возвращает гео-параметры юзера (ids)
     *
     * @return array
     */
    private function getUserGeoParams($userId) {
        $Mamba = $this->get('mamba');
        $userInfo = $Mamba->Anketa()->getInfo($userId);
        $userGeoParams = $userInfo[0]['location'];

        $geoParams = array(
            'country_id' => null,
            'region_id'  => null,
            'city_id'    => null,
        );

        list($countryName, $regionName, $cityName) = array_values($userGeoParams);
        if ($geoParams['country_id'] = $this->parseCountryId($countryName)) {
            if ($geoParams['region_id'] = $this->parseRegionId($geoParams['country_id'], $regionName)) {
                $geoParams['city_id'] = $this->parseCityId($geoParams['region_id'], $cityName);
            }
        }

        return $geoParams;
    }

    /**
     * Возвращает id страны по имени
     *
     * @return str|null
     */
    private function parseCountryId($countryName) {
        foreach ($this->get('mamba')->Geo()->getCountries() as $country) {
            list($id, $name) = array_values($country);
            if ($name == $countryName) {
                return $id;
            }
        }
    }

    /**
     * Возвращает id региона по имени и id страны
     *
     * @param $countryId
     * @param $regionName
     */
    private function parseRegionId($countryId, $regionName) {
        foreach ($this->get('mamba')->Geo()->getRegions($countryId) as $region) {
            list($id, $name) = array_values($region);
            if ($name == $regionName) {
                return $id;
            }
        }
    }

    /**
     * Возвращает id города по имени и id региона
     *
     * @param $regionId
     * @param $cityName
     */
    private function parseCityId($regionId, $cityName) {
        foreach ($this->get('mamba')->Geo()->getCities($regionId) as $city) {
            list($id, $name) = array_values($city);
            if ($name == $cityName) {
                return $id;
            }
        }
    }

    /**
     * Возвращает массив поисковых предпочтений, полученных из запроса
     *
     * @return array|null
     */
    private function getSearchPreferencesFromRequest() {
        $Request = $this->getRequest();
        if ($Request->getMethod() == 'POST') {
            $postParams = $Request->request->all();
            $requiredParams = array('gender', 'age_from', 'age_to');
            if (count(array_intersect(array_keys($postParams), $requiredParams)) == count($requiredParams)) {
                $searchPreferences = array();
                foreach ($requiredParams as $param) {
                    $searchPreferences[$param] = $postParams[$param];
                }

                $searchPreferences['age_from'] = intval($searchPreferences['age_from']);
                $searchPreferences['age_to'] = intval($searchPreferences['age_to']);

                if (in_array($searchPreferences['gender'], array('M', 'F')) &&
                    $searchPreferences['age_from'] >= 18 &&
                    $searchPreferences['age_to'] <= 80 &&
                    $searchPreferences['age_to'] >= 18
                ) {
                    return $searchPreferences;
                }
            }
        }
    }

    /**
     * Очищает пользовательские очереди
     *
     * @return mixed
     */
    private function cleanUserQueues() {
        $Mamba = $this->get('mamba');
        return
            $this->get('redis')
                ->multi()
                    ->delete(sprintf(EncountersBundle::REDIS_SET_USER_HITLIST_QUEUE_KEY, $Mamba->get('oid')))
                    ->delete(sprintf(EncountersBundle::REDIS_SET_USER_CONTACTS_QUEUE_KEY, $Mamba->get('oid')))
                    ->delete(sprintf(EncountersBundle::REDIS_ZSET_USER_SEARCH_QUEUE_KEY, $Mamba->get('oid')))
                    ->delete(sprintf(EncountersBundle::REDIS_ZSET_USER_CURRENT_QUEUE_KEY, $Mamba->get('oid')))

                    //->delete(sprintf(EncountersBundle::REDIS_SET_USER_REVERSE_QUEUE_KEY, $Mamba->get('oid')))

                ->exec()
        ;
    }

    /**
     * Отправляет задания крон-скриптам на перегенерацию очередей
     *
     * @return null
     */
    private function regenerateUserQueues() {
        $GearmanClient = $this->get('gearman')->getClient();
        $Mamba = $this->get('mamba');

        $GearmanClient->doHighBackground(EncountersBundle::GEARMAN_HITLIST_QUEUE_UPDATE_FUNCTION_NAME, $Mamba->get('oid'));
        $GearmanClient->doHighBackground(EncountersBundle::GEARMAN_CONTACTS_QUEUE_UPDATE_FUNCTION_NAME, $Mamba->get('oid'));
        $GearmanClient->doHighBackground(EncountersBundle::GEARMAN_SEARCH_QUEUE_UPDATE_FUNCTION_NAME, $Mamba->get('oid'));

    }
}