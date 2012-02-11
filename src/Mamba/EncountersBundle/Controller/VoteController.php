<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Symfony\Component\HttpFoundation\Response;
use Mamba\PlatformBundle\API\Mamba;
use Mamba\EncountersBundle\EncountersBundle;

/**
 * VoteController
 *
 * @package EncountersBundle
 */
class VoteController extends ApplicationController {

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
    public function ajaxVoteSetAction() {
        $Mamba = $this->getMamba();
        $Redis = $this->getRedis();

        if (!$Mamba->getReady()) {
            list($this->json['status'], $this->json['message']) = array(1, "Mamba is not ready");
        } elseif (!$this->validateParams()) {
            list($this->json['status'], $this->json['message']) = array(2, "Invalid params");
        } else {

            /** Пишем в хитлист */
            $this->getHitlistObject()->incr($this->currentUserId);

            /** Инкрементируем счетчик выбора у webUser'a */
            $this->getCountersObject()->incr($this->webUserId, 'mychoice');

            /** Инкрементируем счетчик просмотров у currentUser'a */
            $this->getCountersObject()->incr($this->currentUserId, 'visited');

            /** Увеличить энергию WebUser'a */
            $this->getEnergyObject()->incr($this->webUserId, $this->decision + 2);

            /** Уменьшить энергию CurrentUser'a */
            $this->getEnergyObject()->decr($this->cureentUserId, $this->decision + 2);

            /** Если я голосую за тебя положительно, то я должен к тебе в очередь подмешаться */
            if ($this->decision) {
                $this->getPriorityQueueObject()->put($this->currentUserId, $this->webUserId);
            }

            /** Ставим задачу на спам */
            $this->getGearman()->getClient()->doLowBackground(EncountersBundle::GEARMAN_NOTIFICATIONS_SEND_FUNCTION_NAME, serialize($dataArray = array(
                'webUserId' => $this->webUserId,
                'currentUserId' => $this->currentUserId,
                'decision' => $this->decision,
            )));

            /** Ставим задачу на обноления базы */
            $this->getGearman()->getClient()->doLowBackground(EncountersBundle::GEARMAN_DATABASE_UPDATE_FUNCTION_NAME, serialize($dataArray));

            /** Не спишком ли часто мы спамим? */

            /** Может быть мы совпали? */

            /** Удалим currentUser'a из текущей очереди webUser'a */
            $this->getCurrentQueueObject()->remove($this->webUserId, $this->currentUserId);

            /** Добавим currentUser'a в список уже просмотренных webUser'ом */
            $this->getViewedQueueObject()->put($this->webUserId, $this->currentUserId, array('ts'=>time(), 'desicion'=>$this->decision));
        }

        return new Response(json_encode($this->json), 200, array("application/json"));
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