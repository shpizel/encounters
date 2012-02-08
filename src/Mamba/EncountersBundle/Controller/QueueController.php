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
     * AJAX Queue get action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function ajaxQueueGetAction() {
        $Mamba = $this->getMamba();
        $Redis = $this->getRedis();

        if (!$Mamba->getReady()) {
            list($this->json['status'], $this->json['message']) = array(1, "Mamba is not ready");
        }

        $webUserId = $Mamba->get('oid');

        if ($currentQueue = $this->getCurrentQueueObject()->getAll($webUserId)) {
            foreach ($Mamba->Anketa()->getInfo($currentQueue) as $dataArray) {
                $this->json['data'][] = array(
                    'info' => array(
                        'id'      => $dataArray['info']['oid'],
                        'login'   => $dataArray['info']['login'],
                        'name'    => $dataArray['info']['name'],
                        'gender'  => $dataArray['info']['gender'],
                        'age'     => $dataArray['info']['age'],
                        'country' => $dataArray['location']['country'],
                        'city'    => $dataArray['location']['city'],
                    ),
                );
            }

            $Mamba->multi();
            foreach ($currentQueue as $userId) {
                $Mamba->Photos()->get($userId);
            }

            foreach ($Mamba->exec() as $key=>$dataArray) {
                if (isset($dataArray['photos'])) {
                    $this->json['data'][$key]['photos'] = $dataArray['photos'];
                }
            }

            foreach ($this->json['data'] as $key=>$dataArray) {
                $userId = $dataArray['info']['id'];
                if (!isset($dataArray['photos'])) {
                    unset($this->json['data'][$key]);

                    $this->getCurrentQueueObject()->remove($webUserId, $userId);
                }
            }

        } else {
            list($this->json['status'], $this->json['message']) = array(2, "Current queue is not ready");

            $this->get('gearman')->getClient()
                ->doHighBackground(EncountersBundle::GEARMAN_CURRENT_QUEUE_UPDATE_FUNCTION_NAME, $webUserId);
        }

        return new Response(json_encode($this->json), 200, array("application/json"));
    }
}