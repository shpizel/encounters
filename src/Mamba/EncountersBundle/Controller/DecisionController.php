<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Symfony\Component\HttpFoundation\Response;
use Mamba\PlatformBundle\API\Mamba;
use Mamba\EncountersBundle\EncountersBundle;

/**
 * DecisionController
 *
 * @package EncountersBundle
 */
class DecisionController extends ApplicationController {

    protected

        /**
         * JSON Result
         *
         * @var array
         */
        $json = array(
            'status'  => 0,
            'message' => '',
            'data'    => array(

            ),
        ),

        /**
         * Требуемые параметры
         *
         * @var array
         */
        $requiredParams = array(
            'user_id',
            'decision',
        )
    ;

    /**
     * AJAX vote setter action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function setDecisionAction() {
        $Mamba = $this->getMamba();

        if (!$Mamba->getReady()) {
            list($this->json['status'], $this->json['message']) = array(1, "Mamba is not ready");
        } elseif (!$this->validateParams()) {
            list($this->json['status'], $this->json['message']) = array(2, "Invalid params");
        } else {
            $levelUp = false;

            /** Инкрементируем счетчик выбора у webUser'a */
            if ($this->decision + 1 > 0) {
                $this->getCountersObject()->incr($this->webUserId, 'mychoice');
            }

            /** Инкрементируем счетчик просмотров у currentUser'a */
            $this->getCountersObject()->incr($this->currentUserId, 'visitors');
            $this->getCountersObject()->incr($this->currentUserId, 'visitors_unread');

            /** Увеличить энергию WebUser'a */
            $webUserEnergy = $this->getEnergyObject()->get($this->webUserId);
            $webUserLevel = $this->getPopularityObject()->getLevel($webUserEnergy);

            $this->getEnergyObject()->incr($this->webUserId, $this->decision + 2);

            $webUserEnergy = $this->getEnergyObject()->get($this->webUserId);
            if (($newWebUserLevel = $this->getPopularityObject()->getLevel($webUserEnergy)) > $webUserLevel) {
                $cacheKey = "user_{$this->webUserId}_level_$newWebUserLevel";

                if (!$this->getRedis()->get($cacheKey)) {
                    $this->getRedis()->setex($cacheKey, 24*3600, 1);
                    $levelUp = true;
                }
            }


            /** Уменьшить энергию CurrentUser'a */
            $currentUserEnergy = $this->getEnergyObject()->get($this->currentUserId);
            $currentUserLevel = $this->getPopularityObject()->getLevel($currentUserEnergy);
            $this->getEnergyObject()->decr($this->currentUserId, (($currentUserLevel < 3) ? 3 : $currentUserLevel));

            /** Если я голосую за тебя положительно, то я должен к тебе в очередь подмешаться */
            if ($this->decision + 1 > 0) {

                /**
                 * @todo: Сделать это через крон-скрипт, учитывая пол, возраст, страну итд
                 *
                 * @author shpizel
                 */
                if (($currentUserSearchPreferences = $this->getSearchPreferencesObject()->get($this->currentUserId)) && ($info = $Mamba->Anketa()->getInfo($this->webUserId, array()))) {
                    if ($info[0]['info']['gender'] == $currentUserSearchPreferences['gender']) {
                        if (!$this->getViewedQueueObject()->get($this->currentUserId, $this->webUserId)) {
                            $this->getPriorityQueueObject()->put($this->currentUserId, $this->webUserId);
                        }
                    }
                }
            }

            $dataArray = array(
                'webUserId' => $this->webUserId,
                'currentUserId' => $this->currentUserId,
                'decision' => $this->decision,
                'time'     => time(),
            );

            $this->getGearman()->getClient()->doLowBackground(EncountersBundle::GEARMAN_NOTIFICATIONS_SEND_FUNCTION_NAME, serialize($dataArray));

            /** Ставим задачу на обноления базы */
            $this->getGearman()->getClient()->doLowBackground(EncountersBundle::GEARMAN_DATABASE_DECISIONS_UPDATE_FUNCTION_NAME, serialize($dataArray));

            /** Не спишком ли часто мы спамим? */
            if (false) {

            }

            /** Может быть мы совпали? */
            if (($this->decision + 1) && ($mutual = $this->getViewedQueueObject()->get($this->currentUserId, $this->webUserId))) {
                if ($mutual['decision'] + 1) {
                    $this->json['data'] = array(
                        'mutual' => true,
                    );

                    $this->getPurchasedObject()->add($this->webUserId, $this->currentUserId);
                    $this->getPurchasedObject()->add($this->currentUserId, $this->webUserId);

                    $this->getCountersObject()->incr($this->webUserId, 'mutual');
                    $this->getCountersObject()->incr($this->currentUserId, 'mutual');

                    $this->getCountersObject()->incr($this->currentUserId, 'mutual_unread');
                }
            }

            /** Удалим currentUser'a из текущей очереди webUser'a */
            $this->getCurrentQueueObject()->remove($this->webUserId, $this->currentUserId);

            /** Добавим currentUser'a в список уже просмотренных webUser'ом */
            $this->getViewedQueueObject()->put($this->webUserId, $this->currentUserId, array('ts'=>time(), 'decision'=>$this->decision));

            $this->json['data']['counters'] = array(
                'visitors' => (int) $this->getCountersObject()->get($this->webUserId, 'visitors'),
                'mychoice' => (int) $this->getCountersObject()->get($this->webUserId, 'mychoice'),
                'mutual'   => (int) $this->getCountersObject()->get($this->webUserId, 'mutual'),
            );

            $this->json['data']['popularity'] = array_merge($this->getPopularityObject()->getInfo($this->getEnergyObject()->get($this->webUserId)), array('level_up' => $levelUp));
        }

        return
            new Response(json_encode($this->json), 200, array(
                    "content-type" => "application/json",
                )
            )
        ;
    }

    /**
     * AJAX Queue get action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getDecisionAction() {
        if ($currentUserId = (int) $this->getRequest()->request->get('user_id')) {
            if ($webUserId = $this->getSession()->get(Mamba::SESSION_USER_ID_KEY)) {

                if ($this->getPurchasedObject()->exists($webUserId, $currentUserId)) {
                    if ($decision = $this->getViewedQueueObject()->get($currentUserId, $webUserId)) {
                        $decision = $decision['decision'];
                    } else {
                        $decision = false;
                    }

                    $this->json['data'] = array(
                        'decision' => $decision,
                    );
                } elseif ($charge = (int) $this->getBatteryObject()->get($webUserId)) {
                    if ($decision = $this->getViewedQueueObject()->get($currentUserId, $webUserId)) {
                        $decision = $decision['decision'];
                    } else {
                        $decision = false;
                    }

                    $this->json['data'] = array(
                        'decision' => $decision,
                        'charge'   => $this->getBatteryObject()->decr($webUserId),
                    );

                    $this->getPurchasedObject()->add($webUserId, $currentUserId);
                } else {
                    list($this->json['status'], $this->json['message']) = array(3, "Battery charge is empty");
                }
            } else {
                list($this->json['status'], $this->json['message']) = array(2, "Invalid session");
            }
        } else {
            list($this->json['status'], $this->json['message']) = array(1, "Invalid params");
        }

        return
            new Response(json_encode($this->json), 200, array(
                    "content-type" => "application/json",
                )
            )
        ;
    }

    /**
     * AJAX Queue get action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function removeDecisionAction() {
        if ($currentUserId = (int) $this->getRequest()->request->get('user_id')) {
            if ($webUserId = $this->getSession()->get(Mamba::SESSION_USER_ID_KEY)) {
                $this->getViewedQueueObject()->put($webUserId, $currentUserId, array());
                if (!$this->getDoctrine()->getEntityManager()->getConnection()->prepare("DELETE FROM Decisions WHERE web_user_id = $webUserId AND current_user_id = $currentUserId LIMIT 1")->execute()) {
                    list($this->json['status'], $this->json['message']) = array(3, "SQL query returned error");
                }

            } else {
                list($this->json['status'], $this->json['message']) = array(2, "Invalid session");
            }
        } else {
            list($this->json['status'], $this->json['message']) = array(1, "Invalid params");
        }

        return
            new Response(json_encode($this->json), 200, array(
                    "content-type" => "application/json",
                )
            )
        ;
    }

    /**
     * Проверяет пришедшие пар-ры
     *
     * @return bool
     */
    private function validateParams() {
        $postParams = $this->getRequest()->request->all();

        if (count(array_intersect(array_keys($postParams), $this->requiredParams)) == count($this->requiredParams)) {
            $params = array();
            foreach ($this->requiredParams as $param) {
                $params[$param] = $postParams[$param];
            }

            list($currentUserId, $decision) = array_values($params);
            $currentUserId = (int) $currentUserId;
            $decision = (int) $decision;

            if ($currentUserId && $decision >= -1 && $decision <= 1) {
                if (false !== $this->getCurrentQueueObject()->exists($this->webUserId = (int) $this->getMamba()->get('oid'), $currentUserId)) {
                    $this->currentUserId = (int) $currentUserId;
                    $this->decision = $decision;

                    return true;
                }
            }
        }
    }
}
