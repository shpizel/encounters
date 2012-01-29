<?php
namespace Mamba\EncountersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Mamba\PlatformBundle\API\Mamba;
use Mamba\EncountersBundle\EncountersBundle;

/**
 * VoteController
 *
 * @package EncountersBundle
 */
class VoteController extends Controller {

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
            'verdict',
        )
    ;

    /**
     * AJAX vote setter action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function ajaxVoteSetAction() {
        $Mamba = $this->get('mamba');
        if (!$Mamba->getReady()) {
            list($this->json['status'], $this->json['message']) = array(1, "Mamba is not ready");
        }

        $Redis = $this->get('redis');

        if (!($params = $this->getParams())) {
            list($this->json['status'], $this->json['message']) = array(2, "Invalid params");
        } else {
            list($userId, $verdict) = array_values($params);

            /** Проверим не слишком ли часто этот пользователь голосует */
            if (false == "СЛИШКОМ ЧАСТО") {
                list($this->json['status'], $this->json['message']) = array(3, "");
            }

            /** Вставить юзера в список уже оцененных */
            $Redis->zAdd(sprintf(EncountersBundle::REDIS_HASH_USER_VIEWED_USERS_KEY, $Mamba->get('oid')), $userId, array('ts'=>time(), 'verdict'=>$verdict));

            /** Удалить из текущей очереди */
            $Redis->zDelete(sprintf(EncountersBundle::REDIS_ZSET_USER_CURRENT_QUEUE_KEY, $Mamba->get('oid')), $userId);

            /** Сформировать массив данных на выход */
            $this->json['data'] = array(/** если все ок то может быть тут ничего и не нужно */);
        }

        return new Response(json_encode($this->json), 200, array("application/json"));
    }

    /**
     * Проверяет и возвращает параметры или false в случае если что-то пошло не так
     *
     * @return bool
     */
    private function getParams() {
        $Request = $this->getRequest();
        $Redis = $this->get('redis');
        $Mamba = $this->get('mamba');
        $postParams = $Request->request->all();
        if (array_intersect(array_keys($postParams), $this->requiredParams) == count($this->requiredParams)) {
            $params = array();
            foreach ($this->requiredParams as $param) {
                $params[$params] = $postParams[$param];
            }

            list($userId, $verdict) = array_values($params);
            if (is_numeric($userId) && is_numeric($verdict)) {
                $userId  = (int) $userId;
                $verdict = (int) $verdict;

                /**
                 * Есть ли этот юзер в текущей очереди
                 *
                 * @author shpizel
                 */
                if ($Redis->zScore(sprintf(EncountersBundle::REDIS_ZSET_USER_CURRENT_QUEUE_KEY, $Mamba->get('oid')), $userId)) {
                    if ($verdict >= -1 && $verdict <= 1) {
                        return array(
                            'user_id' => $userId,
                            'verdict' => $verdict,
                        );
                    }
                }
            }
        }

        return false;
    }
}