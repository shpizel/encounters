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

            # Увеличить энергию WebUser'a
            try {
                $this->getEnergyObject()->incr($this->webUserId, $this->decision + 2);

                # энергия webuser'a увеличилась, а следовательно — нужно обновить его приоритет в чужих очередях
                # ставим задачу
            } catch (\Exception $e) {

            };

            # проверить не слишком ли мы часто голосуем епт

            # а мы не совпали случайно?!

            # записать голосование как-то надо в базу (через задачу)

            # спам?

            # удалим юзера из текущей очереди
            $Redis->zDelete(sprintf(EncountersBundle::REDIS_ZSET_USER_CURRENT_QUEUE_KEY, $this->webUserId), $this->currentUserId);

            # вставим юзера в список уже оцененных
            $Redis->zAdd(sprintf(EncountersBundle::REDIS_HASH_USER_VIEWED_USERS_KEY, $this->webUserId), $this->currentUserId, array('ts' => time(), 'decision' => $this->decision));
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
                if (false !== $this->getRedis()->zScore(sprintf(EncountersBundle::REDIS_ZSET_USER_CURRENT_QUEUE_KEY, $this->webUserId = $this->getMamba()->get('oid')), $userId)) {
                    $this->currentUserId = $userId;
                    $this->decision = $decision;

                    return true;
                }
            }
        }
    }
}