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

            /** Увеличить энергию WebUser'a */
            $this->getEnergyObject()->incr($this->webUserId, $this->decision + 2);

            /** Ставим задачу на обновление памяти */
            $this->getGearman()->getClient()->doLowBackground('', serialize(array(

            )));

            /** Ставим задачу на спам */
            $this->getGearman()->getClient()->doLowBackground('', serialize(array(

            )));

            /** Ставим задачу на обноления базы */
            $this->getGearman()->getClient()->doLowBackground('', serialize(array(

            )));

            /** Не спишком ли часто мы спамим? */


            /** Может мы совпали? */


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

            list($userId, $decision) = array_values($params);
            $userId = (int) $userId;
            $decision = (int) $decision;

            if ($userId && $decision >= -1 && $decision <= 1) {
                if (false !== $this->getRedis()->zScore(sprintf(EncountersBundle::REDIS_ZSET_USER_CURRENT_QUEUE_KEY, $this->webUserId = (int) $this->getMamba()->get('oid')), $userId)) {
                    $this->currentUserId = (int) $userId;
                    $this->decision = $decision;

                    return true;
                }
            }
        }
    }
}