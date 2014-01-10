<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Symfony\Component\HttpFoundation\Response;
use Core\MambaBundle\API\Mamba;
use Mamba\EncountersBundle\EncountersBundle;
use Mamba\EncountersBundle\Command\CurrentQueueUpdateCommand;

/**
 * QueueController
 *
 * @package EncountersBundle
 */
class QueueController extends ApplicationController {

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
        )
    ;

    /**
     * Queue adder
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addAction() {
        $cost = 10;

        if ($webUserId = (int) $this->getSession()->get(Mamba::SESSION_USER_ID_KEY)) {
            if ($currentUserId = (int) $this->getRequest()->request->get('user_id')) {
                $Account = $this->getAccountHelper();
                $account = $Account->get($webUserId);

                if ($account >= $cost) {
                    $account = $Account->decr($webUserId, $cost);
                    $this->getPriorityQueueHelper()->put($currentUserId, $webUserId);

                    $this->json['data'] = array(
                        'account' => $account,
                    );
                } else {
                    list($this->json['status'], $this->json['message']) = array(3, "Account is not enough for level up");
                }
            } else {
                list($this->json['status'], $this->json['message']) = array(2, "Invalid params");
            }
        } else {
            list($this->json['status'], $this->json['message']) = array(1, "Invalid session");
        }

        return $this->JSONResponse($this->json);
    }

    /**
     * Queue getter action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getQueueAction() {
        $Mamba = $this->getMamba();

        if (!$Mamba->getReady()) {
            list($this->json['status'], $this->json['message']) = array(1, "Mamba is not ready");
        } elseif (($webUserId = $this->getMamba()->getWebUserId()) && ($currentQueue = $this->getCurrentQueueHelper()->getAll($webUserId))) {
            $anketaInfoArray = $this->getUsersHelper()->getInfo(
                array_map(function($el){return (int) $el;},  $currentQueue)
            );

            foreach ($anketaInfoArray as $_userId => $dataArray) {
                    if
                    (
                        !(
                            isset($dataArray['location']) &&
                            isset($dataArray['flags']) &&
                            isset($dataArray['familiarity']) &&
                            count($dataArray['photos'])
                        )
                    ) {
                        $currentUserId = $dataArray['info']['user_id'];

                        $this->getCurrentQueueHelper()->remove($webUserId, (int) $currentUserId);
                        $this->getViewedQueueHelper()->put($webUserId, (int) $currentUserId, array('error' => 1));

                        continue;
                    }

                    $this->json['data'][$dataArray['info']['user_id']] = array(
                        'info' => array(
                            'id'               => $dataArray['info']['user_id'],
                            'name'             => $dataArray['info']['name'],
                            'gender'           => $dataArray['info']['gender'],
                            'age'              => $dataArray['info']['age'],
                            'sign'             => $dataArray['info']['sign'],
                            'small_photo_url'  => $dataArray['avatar']['small_photo_url'],
                            'medium_photo_url' => $dataArray['avatar']['medium_photo_url'],
                            'is_app_user'      => $dataArray['info']['is_app_user'],
                            'location'         => $dataArray['location'],
                            'flags'            => $dataArray['flags'],
                            'familiarity'      => $dataArray['familiarity'],
                        ),
                    );
            }

            /**
             * Пересчет currentUser'ов
             *
             * @author shpizel
             */
            $currentUsers = array();
            foreach ($this->json['data'] as $userInfo) {
                if (isset($userInfo['info']['id'])) {
                    $currentUsers[] = (int) $userInfo['info']['id'];
                }
            }

            if ($currentUsers) {
                $dataArray = $this->getUsersHelper()->getInfo($currentUsers, ['info', 'photos']);

                foreach ($dataArray as $_userId => $dataArray) {
                    $this->json['data'][$_userId]['photos'] = $dataArray['photos'];
                }

                foreach ($this->json['data'] as $key => $dataArray) {
                    $currentUserId = $dataArray['info']['id'];
                    if (!isset($dataArray['photos'])) {
                        unset($this->json['data'][$key]);

                        $this->getCurrentQueueHelper()->remove($webUserId, $currentUserId);
                    }
                }
            } else {
                foreach ($currentQueue as $currentUserId) {
                    $this->getCurrentQueueHelper()->remove($webUserId, (int)$currentUserId);
                    $this->getViewedQueueHelper()->put($webUserId, (int)$currentUserId, array('error' => 1));
                }
            }
        }

        isset($webUserId) && $this->get('gearman')->getClient()
            ->doHighBackground(EncountersBundle::GEARMAN_CURRENT_QUEUE_UPDATE_FUNCTION_NAME, serialize(array(
            'user_id'   => $webUserId,
            'timestamp' => time(),
        )));

        if (!$this->json['data']) {
            list($this->json['status'], $this->json['message']) = array(2, "Current queue is not ready");
        } else {
            $this->json['data'] = array_values($this->json['data']);
        }

        return $this->JSONResponse($this->json);
    }
}