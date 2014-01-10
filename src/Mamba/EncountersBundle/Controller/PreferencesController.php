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

        $redisSearchPreferences = $this->getSearchPreferencesHelper()->get($webUserId = $this->getMamba()->getWebUserId());

        if ($searchPreferences = $this->getSearchPreferencesFromRequest()) {

            $webUserAnketa = $this->getUsersHelper()->getInfo($webUserId, ['info', 'location'])[$webUserId];

            $searchPreferences['geo'] = array(
                'country_id' => (isset($webUserAnketa['location']['country']['id'])) ? $webUserAnketa['location']['country']['id'] : null,
                'region_id'  => (isset($webUserAnketa['location']['region']['id'])) ? $webUserAnketa['location']['region']['id'] : null,
                'city_id'    => (isset($webUserAnketa['location']['city']['id'])) ? $webUserAnketa['location']['city']['id'] : null,
            );

            $searchPreferences['orientation'] = intval($webUserAnketa['info']['gender'] != $searchPreferences['gender']);

            $this->getSearchPreferencesHelper()->set($webUserId, $searchPreferences);

            $this->getGearman()->getClient()->doHighBackground(
                EncountersBundle::GEARMAN_DATABASE_USERS_UPDATE_FUNCTION_NAME,
                serialize(
                    array(
                        'users' => [$webUserId],
                        'time'  => time(),
                    )
                )
            );

            /**
             * Изменились ли настройки? Удалим из $redisSearchPreferences ключ changed и сравним массивы json_encode
             *
             * @author shpizel
             */
            $settingsChanged = false;
            if (!$redisSearchPreferences) {
                $settingsChanged = true;
            } else {
                unset($redisSearchPreferences['changed']);
                $settingsChanged = json_encode($redisSearchPreferences) != json_encode($searchPreferences);
            }

            if ($settingsChanged) {
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

        return $this->TwigResponse('EncountersBundle:templates:preferences.html.twig', $initialData);
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

        $Redis->delete($this->getHitlistQueueHelper()->getRedisQueueKey($webUserId = $this->getMamba()->getWebUserId()));
        $Redis->delete($this->getContactsQueueHelper()->getRedisQueueKey($webUserId));
        $Redis->delete($this->getSearchQueueHelper()->getRedisQueueKey($webUserId));

        /**
         * По идее тут втупую удалять не нужно, потому что тут могут быть пользователи из PriorityQueue
         *
         * @author shpizel
         */
        $Redis->delete($this->getCurrentQueueHelper()->getRedisQueueKey($webUserId));
    }
}