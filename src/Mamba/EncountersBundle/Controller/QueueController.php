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
            $currentQueue = array_reverse($currentQueue);
            $currentQueue = array_chunk($currentQueue, 100);
            foreach ($currentQueue as $key=>$subQueue) {
                $currentQueue[$key] = array_reverse($subQueue);
            }
            $currentQueue = array_reverse($currentQueue);

            $Mamba->multi();
            foreach ($currentQueue as $subQueue) {
                $Mamba->Anketa()->getInfo($subQueue);
            }
            $anketaInfoArray = $Mamba->exec();

            foreach ($anketaInfoArray as $chunk) {
                foreach ($chunk as $dataArray) {

                    if (!(isset($dataArray['location']) && isset($dataArray['flags']) && isset($dataArray['familiarity']))) {
                        $currentUserId = $dataArray['info']['oid'];

                        $this->getCurrentQueueHelper()->remove($webUserId, (int)$currentUserId);
                        $this->getViewedQueueHelper()->put($webUserId, (int)$currentUserId, array('error' => 1));

                        continue;
                    }

                    $this->json['data'][] = array(
                        'info' => array(
                            'id'               => $dataArray['info']['oid'],
                            'name'             => $dataArray['info']['name'],
                            'gender'           => $dataArray['info']['gender'],
                            'age'              => $dataArray['info']['age'],
                            'sign'             => $dataArray['info']['sign'],
                            'small_photo_url'  => $dataArray['info']['small_photo_url'],
                            'medium_photo_url' => $dataArray['info']['medium_photo_url'],
                            'is_app_user'      => $dataArray['info']['is_app_user'],
                            'location'         => $dataArray['location'],
                            'flags'            => $dataArray['flags'],
                            'familiarity'      => $dataArray['familiarity'],
                        ),
                    );
                }
            }

            /**
             * Пересчет currentUser'ов
             *
             * @author shpizel
             */
            $currentUsers = array();
            foreach ($this->json['data'] as $userInfo) {
                if (isset($userInfo['info']['id'])) {
                    $currentUsers[] = $userInfo['info']['id'];
                }
            }

            if ($currentUsers) {
                $Mamba->multi();
                foreach ($currentUsers as $currentUserId) {
                    $Mamba->Photos()->get($currentUserId);
                }
                $dataArray = $Mamba->exec();

                foreach ($dataArray as $key=>$dataArray) {
                    if (isset($dataArray['photos'])) {
                        $this->json['data'][$key]['photos'] = $dataArray['photos'];
                    }
                }

                foreach ($this->json['data'] as $key=>$dataArray) {
                    $currentUserId = $dataArray['info']['id'];
                    if (!isset($dataArray['photos'])) {
                        unset($this->json['data'][$key]);

                        $this->getCurrentQueueHelper()->remove($webUserId, $currentUserId);
                    }
                }
            } else {
                foreach ($currentQueue as $chunk) {
                    foreach ($chunk as $currentUserId) {
                        $this->getCurrentQueueHelper()->remove($webUserId, (int)$currentUserId);
                        $this->getViewedQueueHelper()->put($webUserId, (int)$currentUserId, array('error' => 1));
                    }
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