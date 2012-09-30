<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Core\MambaBundle\API\Mamba;
use Mamba\EncountersBundle\EncountersBundle;

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

        $redisSearchPreferences = $this->getSearchPreferencesObject()->get($webUserId = $Mamba->get('oid'));

        if ($searchPreferences = $this->getSearchPreferencesFromRequest()) {

            $webUserAnketa = $this->getMamba()->Anketa()->getInfo($webUserId);

            $searchPreferences['geo'] = array(
                'country_id' => (isset($webUserAnketa[0]['location']['country_id'])) ? $webUserAnketa[0]['location']['country_id'] : null,
                'region_id'  => (isset($webUserAnketa[0]['location']['region_id'])) ? $webUserAnketa[0]['location']['region_id'] : null,
                'city_id'    => (isset($webUserAnketa[0]['location']['city_id'])) ? $webUserAnketa[0]['location']['city_id'] : null,
            );

            $searchPreferences['orientation'] = intval($webUserAnketa[0]['info']['gender'] != $searchPreferences['gender']);

            $this->getSearchPreferencesObject()->set($webUserId, $searchPreferences);

            $this->getGearman()->getClient()->doHighBackground(
                EncountersBundle::GEARMAN_DATABASE_USER_UPDATE_FUNCTION_NAME,
                serialize(
                    array(
                        'user_id'     => $webUserId,
                        'gender'      => $webUserAnketa[0]['info']['gender'],
                        'orientation' => intval($webUserAnketa[0]['info']['gender'] != $searchPreferences['gender']),
                        'age'         => $webUserAnketa[0]['info']['age'],
                        'country_id'  => $searchPreferences['geo']['country_id'],
                        'region_id'   => $searchPreferences['geo']['region_id'],
                        'city_id'     => $searchPreferences['geo']['city_id'],
                    )
                )
            );

            /**
             * Изменились ли настройки?
             *
             * @author shpizel
             */
            if (!$redisSearchPreferences || $diff = array_diff($redisSearchPreferences, $searchPreferences)) {
                if (!(isset($diff['changed']) && count($diff) == 1)) {
                    $this->cleanUserQueues();

                    $GearmanClient = $this->getGearman()->getClient();

                    $GearmanClient->doHighBackground(EncountersBundle::GEARMAN_SEARCH_QUEUE_UPDATE_FUNCTION_NAME, serialize(array(
                        'user_id'   => $webUserId,
                        'timestamp' => time(),
                    )));

                    $GearmanClient->doHighBackground(EncountersBundle::GEARMAN_HITLIST_QUEUE_UPDATE_FUNCTION_NAME, serialize(array(
                        'user_id'   => $webUserId,
                        'timestamp' => time(),
                    )));

                    $GearmanClient->doHighBackground(EncountersBundle::GEARMAN_CONTACTS_QUEUE_UPDATE_FUNCTION_NAME, serialize(array(
                        'user_id'   => $webUserId,
                        'timestamp' => time(),
                    )));
                }
            }

            return $this->redirect($this->generateUrl('welcome'));
        } else {
            $searchPreferences = $redisSearchPreferences;
        }

        if (!$searchPreferences) {
            if ($anketaInfo = $Mamba->nocache()->Anketa()->getInfo($webUserId)) {
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
                    }  elseif ($age = $anketaInfo['info']['age']) {
                        $searchPreferences['age_from'] = $age -5;
                        $searchPreferences['age_to'] = $age + 5;
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

            $searchPreferences['age_from'] = isset($searchPreferences['age_from']) ? $searchPreferences['age_from'] : 18;
            $searchPreferences['age_to']   = isset($searchPreferences['age_to']) ? $searchPreferences['age_to'] : 25;
        }

        $initialData = $this->getInitialData();
        $initialData['webuser']['preferences'] = $searchPreferences;

        $Response = $this->render('EncountersBundle:templates:preferences.html.twig', $initialData);
        $Response->headers->set('P3P', 'CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
        return $Response;
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

                if ($searchPreferences['age_from'] <= $searchPreferences['age_to'] &&
                    in_array($searchPreferences['gender'], array('M', 'F')) &&
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
        $Redis = $this->getRedis();

        $Redis->delete($this->getHitlistQueueObject()->getRedisQueueKey($webUserId = $this->getMamba()->get('oid')));
        $Redis->delete($this->getContactsQueueObject()->getRedisQueueKey($webUserId));
        $Redis->delete($this->getSearchQueueObject()->getRedisQueueKey($webUserId));

        /**
         * По идее тут втупую удалять не нужно, потому что тут могут быть пользователи из PriorityQueue
         *
         * @author shpizel
         */
        $Redis->delete($this->getCurrentQueueObject()->getRedisQueueKey($webUserId));
    }
}