<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Symfony\Component\HttpFoundation\Response;
use Core\MambaBundle\API\Mamba;
use Mamba\EncountersBundle\EncountersBundle;

use Mamba\EncountersBundle\Command\SearchQueueUpdateCommand;
use Mamba\EncountersBundle\Command\HitlistQueueUpdateCommand;
use Mamba\EncountersBundle\Command\ContactsQueueUpdateCommand;

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
        $Redis = $this->getRedis();

        if (!$Mamba->getReady()) {
            list($this->json['status'], $this->json['message']) = array(1, "Mamba is not ready");
        } elseif (!$this->validateParams()) {
            list($this->json['status'], $this->json['message']) = array(2, "Invalid params");
        } else {
            $levelUp = false;

            /** Инкрементируем счетчик выбора у webUser'a */
            if ($this->decision + 1 > 0) {
                $this->getCountersHelper()->incr($this->webUserId, 'mychoice');
            }

            /** Обновляем переменные */
            $this->getVariablesHelper()->set($this->webUserId, 'last_outgoing_decision', time());
            $this->getVariablesHelper()->set($this->currentUserId, 'last_incoming_decision', time());

            /** увеличиваем счетчик дневных голосований в мемкеше */
            $dailyDecisionsCounter = $this->getMemcache()->increment($dailyDecisionsCounterMemcacheKey = $this->webUserId . '_daily_decisions_counter_by_' . date("Ymd"));
            if (false == $dailyDecisionsCounter) {
                $this->getMemcache()->add($dailyDecisionsCounterMemcacheKey, $dailyDecisionsCounter = 1);
            }

            /** Инкрементируем счетчик просмотров у currentUser'a */
            $this->getCountersHelper()->incr($this->currentUserId, 'visitors');
            $this->getCountersHelper()->incr($this->currentUserId, 'visitors_unread');

            $this->getCountersHelper()->set($this->currentUserId, 'updated', time());

            /** Увеличить энергию WebUser'a */
            $webUserEnergy = $this->getEnergyHelper()->get($this->webUserId);
            $webUserLevel = $this->getPopularityHelper()->getLevel($webUserEnergy);

            /** увеличиваем энергию веб-юзера на 100-300 очков базово и делим на логарифмический делитель */
            $log = log($dailyDecisionsCounter, 16);
            if ($log < 1) {
                $log = 1; //бодрости не будет
            }

            $this->getEnergyHelper()->incr($this->webUserId, (int) /** важно чтобы был инт */round(($this->decision + 2)*100/$log, 0));

            $webUserEnergy = $this->getEnergyHelper()->get($this->webUserId);
            if (($newWebUserLevel = $this->getPopularityHelper()->getLevel($webUserEnergy)) > $webUserLevel) {
                $cacheKey = "user_{$this->webUserId}_level_$newWebUserLevel";

                if (!$this->getRedis()->get($cacheKey)) {
                    $this->getRedis()->setex($cacheKey, 24*3600, 1);
                    $levelUp = true;
                }
            }

            /** Уменьшить энергию CurrentUser'a */
            /*if ($this->getSearchPreferencesObject()->exists($this->currentUserId)) {
                if (($currentUserEnergy = $this->getEnergyObject()->get($this->currentUserId)) > 0) {
                    $currentUserLevel = $this->getPopularityObject()->getLevel($currentUserEnergy);

                    $levels = $this->getPopularityObject()->getLevels();
                    if (isset($levels[$currentUserLevel + 1])) {
                        $energiesInterval = array(
                            'from' => $levels[$currentUserLevel],
                            'to'   => $levels[$currentUserLevel + 1],
                        );
                    } else {
                        $energiesInterval = array(
                            'from' => $levels[$currentUserLevel - 1],
                            'to'   => $levels[$currentUserLevel],
                        );
                    }

                    $points = intval(($energiesInterval['to'] - $energiesInterval['from']) / $currentUserLevel);
                    $this->getEnergyObject()->decr($this->currentUserId, $points*2);
                }
            }*/

            /** откатились на старую версию */
            /** Уменьшить энергию CurrentUser'a */
            if ($this->getSearchPreferencesHelper()->exists($this->currentUserId)) {
                $currentUserEnergy = $this->getEnergyHelper()->get($this->currentUserId);
                $currentUserLevel = $this->getPopularityHelper()->getLevel($currentUserEnergy);

                /** мутная функция */
                $this->getEnergyHelper()->decr($this->currentUserId, 100*5*(($currentUserLevel < 3) ? 3 : $currentUserLevel));
            }

            /** Если я голосую за тебя положительно, то я должен к тебе в очередь подмешаться */
            if ($this->decision + 1 > 0) {
                if (($currentUserSearchPreferences = $this->getSearchPreferencesHelper()->get($this->currentUserId)) && ($webUserInfo = $Mamba->Anketa()->getInfo($this->webUserId))) {
                    $webUserInfo = array_shift($webUserInfo);
                    $webUserInfo = $webUserInfo['info'];

                    if ($webUserInfo['gender'] == $currentUserSearchPreferences['gender'] &&
                        $webUserInfo['age'] >= $currentUserSearchPreferences['age_from'] &&
                        $webUserInfo['age'] <= $currentUserSearchPreferences['age_to']
                    ) {
                        if (!$this->getViewedQueueHelper()->get($this->currentUserId, $this->webUserId)) {
                            $this->getPriorityQueueHelper()->put($this->currentUserId, $this->webUserId);
                        }
                    }
                }

                if ($Redis->sIsMember("contacts_by_{$this->webUserId}", $this->currentUserId) && !$this->getVariablesHelper()->get($this->currentUserId, 'lastaccess')) {
                    $this->json['data']['is_contact'] = true;
                }
            }

            $dataArray = array(
                'webUserId'     => $this->webUserId,
                'currentUserId' => $this->currentUserId,
                'decision'      => $this->decision,
                'time'          => time(),
            );

            /** Ставим задачу на обновление базы */
            $this->getGearman()->getClient()->doLowBackground(
                EncountersBundle::GEARMAN_DATABASE_DECISIONS_UPDATE_FUNCTION_NAME,
                serialize($dataArray)
            );

            /** Ставим задачу на установку ачивки */
            $this->getGearman()->getClient()->doLowBackground(
                EncountersBundle::GEARMAN_ACHIEVEMENT_SET_FUNCTION_NAME,
                serialize($dataArray)
            );

            /** Не спишком ли часто мы спамим? */
            foreach (range(-1, 1) as $decision) {
                if ($decision == $this->decision) {
                    if ($this->getCountersHelper()->incr($this->webUserId, "noretry-($decision)") >= 25) {
                        $repeatWarningKey = "{$this->webUserId}_repeat_warning";
                        if ($this->getMemcache()->add($repeatWarningKey, 1, 3600)) {
                            $this->json['data']['repeat_warning'] = $decision;
                        }

                        $this->getCountersHelper()->set($this->webUserId, "noretry-($decision)", 0);
                    }
                } else {
                    $this->getCountersHelper()->set($this->webUserId, "noretry-($decision)", 0);
                }
            }

            /** Может быть мы совпали? */
            if (($this->decision + 1) && ($mutual = $this->getViewedQueueHelper()->get($this->currentUserId, $this->webUserId))) {
                if (isset($mutual['decision']) && ($mutual['decision'] + 1)) {
                    $this->json['data']['mutual'] = true;

                    $this->getPurchasedHelper()->add($this->webUserId, $this->currentUserId);
                    $this->getPurchasedHelper()->add($this->currentUserId, $this->webUserId);

                    $this->getCountersHelper()->incr($this->webUserId, 'mutual');
                    $this->getCountersHelper()->incr($this->currentUserId, 'mutual');
                    $this->getCountersHelper()->incr($this->currentUserId, 'mutual_unread');

                    $this->getGearman()->getClient()->doLowBackground(
                        EncountersBundle::GEARMAN_MUTUAL_ICEBREAKER_FUNCTION_NAME,
                        serialize(array(
                            'webUserId'     => $this->webUserId,
                            'currentUserId' => $this->currentUserId,
                            'time'          => time(),
                        ))
                    );
                }
            } else {
                $this->getPurchasedHelper()->remove(intval($this->webUserId), intval($this->currentUserId));
            }

            /** Удалим currentUser'a из текущей очереди webUser'a */
            $this->getCurrentQueueHelper()->remove($this->webUserId, $this->currentUserId);

            /** Добавим currentUser'a в список уже просмотренных webUser'ом */
            $this->getViewedQueueHelper()->put(
                $this->webUserId,
                $this->currentUserId,
                array(
                    'ts'       => time(),
                    'decision' => $this->decision
                )
            );

            $this->json['data']['counters'] = $this->getCountersHelper()->getMulti([$this->webUserId], [
                'visitors',
                'visitors_unread',
                'mutual',
                'mutual_unread',
                'messages_unread',
                'events_unread'
            ])[$this->webUserId];

            /*$this->json['data']['counters'] = array(
                'visitors' => mt_rand(0, 100),
                'visitors_unread' => mt_rand(0, 100),
                'mutual' => mt_rand(0, 100),
                'mutual_unread' => mt_rand(0, 100),
                'messages_unread' => mt_rand(0, 100),
                'events_unread' => mt_rand(0, 100),
            );*/

            $this->json['data']['popularity'] = array_merge(
                $this->getPopularityHelper()->getInfo($this->getEnergyHelper()->get($this->webUserId)),
                array('level_up' => $levelUp)
            );
        }

        return $this->JSONResponse($this->json);
    }

    /**
     * AJAX Queue get action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getDecisionAction() {
        if ($currentUserId = (int) $this->getRequest()->request->get('user_id')) {
            if ($webUserId = $this->getSession()->get(Mamba::SESSION_USER_ID_KEY)) {

                if ($this->getPurchasedHelper()->exists($webUserId, $currentUserId)) {
                    if ($decision = $this->getViewedQueueHelper()->get($currentUserId, $webUserId)) {
                        $decision = $decision['decision'];
                    } else {
                        $decision = false;
                    }

                    $this->json['data'] = array(
                        'decision' => $decision,
                    );

                    $this->getStatsHelper()->incr('decision.get-battery.notrequired');
                } elseif (($charge = (int) $this->getBatteryHelper()->get($webUserId)) || (2 <= $account = $this->getAccountHelper()->get($webUserId))) {
                    if (!$charge) {
                        $multiply = intval($account / 2);
                        if ($multiply > 5) {
                            $multiply = 5;
                        }

                        $account = $this->getAccountHelper()->decr($webUserId, $multiply*2);
                        $this->getBatteryHelper()->incr($webUserId, $multiply);

                        $this->getStatsHelper()->incr('decision.get-battery.charge');
                    }

                    if ($decision = $this->getViewedQueueHelper()->get($currentUserId, $webUserId)) {
                        $decision = $decision['decision'];
                    } else {
                        $decision = false;
                    }

                    $this->json['data'] = array(
                        'decision' => $decision,
                        'charge'   => $this->getBatteryHelper()->decr($webUserId),
                        'account'  => $this->getAccountHelper()->get($webUserId),
                    );

                    $this->getPurchasedHelper()->add($webUserId, $currentUserId);
                    $this->getStatsHelper()->incr('decision.get-battery.decr');
                } else {
                    $this->getStatsHelper()->incr('decision.get-battery.empty');
                    list($this->json['status'], $this->json['message']) = array(3, "Battery charge is empty");
                }
            } else {
                list($this->json['status'], $this->json['message']) = array(2, "Invalid session");
            }
        } else {
            list($this->json['status'], $this->json['message']) = array(1, "Invalid params");
        }

        return $this->JSONResponse($this->json);
    }

    /**
     * AJAX Queue get action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function removeDecisionAction() {
        if ($currentUserId = (int) $this->getRequest()->request->get('user_id')) {
            if ($webUserId = $this->getSession()->get(Mamba::SESSION_USER_ID_KEY)) {
                $currentUserDecision = $this->getViewedQueueHelper()->get($currentUserId, $webUserId);
                $webUserDecision     = $this->getViewedQueueHelper()->get($webUserId, $currentUserId);

                if ($currentUserDecision && $webUserDecision && $currentUserDecision['decision'] >=0 && $webUserDecision['decision'] >= 0) {
                    $this->getViewedQueueHelper()->put($webUserId, $currentUserId, array(
                        'ts'       => time(),
                        'decision' => -1,
                    ));

                    $this->getGearman()->getClient()->doLowBackground(
                        EncountersBundle::GEARMAN_DATABASE_DECISIONS_UPDATE_FUNCTION_NAME,
                        serialize(
                            array(
                                'webUserId' => $webUserId,
                                'currentUserId' => $currentUserId,
                                'decision' => -1,
                                'time'     => time(),
                            )
                        )
                    );

                    /** Обновим счетчики */
                    $this->getCountersHelper()->decr($currentUserId, 'mutual');
                    $this->json['data']['counters'] = array(
                        'visitors' => $this->getCountersHelper()->get($webUserId, 'visitors'),
                        'mutual'   => $this->getCountersHelper()->decr($webUserId, 'mutual'),
                        'mychoice' => $this->getCountersHelper()->get($webUserId, 'mychoice'),
                    );

                    $this->getGearman()->getClient()->doLowBackground(EncountersBundle::GEARMAN_ACHIEVEMENT_SET_FUNCTION_NAME, serialize(array(
                        'webUserId'     => $webUserId,
                        'currentUserId' => null,
                        'decision'      => null,
                        'time'          => time(),
                    )));

                    foreach (range(-1, 1) as $decision) {
                        $this->getCountersHelper()->set($webUserId, "noretry-($decision)", 0);
                    }
                } else {
                    list($this->json['status'], $this->json['message']) = array(2, "Invalid input data");
                }
            } else {
                list($this->json['status'], $this->json['message']) = array(2, "Invalid session");
            }
        } else {
            list($this->json['status'], $this->json['message']) = array(1, "Invalid params");
        }

        return $this->JSONResponse($this->json);
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
                if (false !== $this->getCurrentQueueHelper()->exists($this->webUserId = $this->getMamba()->getWebUserId(), $currentUserId)) {
                    $this->currentUserId = (int) $currentUserId;
                    $this->decision = $decision;

                    return true;
                }
            }
        }
    }
}
