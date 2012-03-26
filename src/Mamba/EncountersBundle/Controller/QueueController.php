<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Symfony\Component\HttpFoundation\Response;
use Mamba\PlatformBundle\API\Mamba;
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
     * Queue getter action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getQueueAction() {
        $Mamba = $this->getMamba();

        if (!$Mamba->getReady()) {
            list($this->json['status'], $this->json['message']) = array(1, "Mamba is not ready");
        } elseif (($webUserId = $Mamba->get('oid')) && ($currentQueue = $this->getCurrentQueueObject()->getAll($webUserId))) {
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
                    $this->json['data'][] = array(
                        'info' => array(
                            'id'               => $dataArray['info']['oid'],
                            'name'             => $dataArray['info']['name'],
                            'gender'           => $dataArray['info']['gender'],
                            'age'              => $dataArray['info']['age'],
                            'small_photo_url'  => $dataArray['info']['small_photo_url'],
                            'medium_photo_url' => $dataArray['info']['medium_photo_url'],
                            'is_app_user'      => $dataArray['info']['is_app_user'],
                            'location'         => $dataArray['location'],
                            'flags'            => $dataArray['flags'],
                            'familiarity'      => $dataArray['familiarity'],
                            'other'            => $dataArray['other'],
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

                        $this->getCurrentQueueObject()->remove($webUserId, $currentUserId);
                    }
                }
            }
        }

        if (!$this->json['data']) {
            list($this->json['status'], $this->json['message']) = array(2, "Current queue is not ready");

            if (isset($webUserId)) {
                $this->get('gearman')->getClient()
                    ->doHighBackground(EncountersBundle::GEARMAN_CURRENT_QUEUE_UPDATE_FUNCTION_NAME, serialize(array(
                    'user_id'   => $webUserId,
                    'timestamp' => time(),
                )));
            }
        }

        return
            new Response(json_encode($this->json), 200, array(
                    "content-type" => "application/json",
                )
            )
        ;
    }
}