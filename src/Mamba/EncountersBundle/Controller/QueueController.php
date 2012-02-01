<?php
namespace Mamba\EncountersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Mamba\PlatformBundle\API\Mamba;
use Mamba\EncountersBundle\EncountersBundle;
use Mamba\EncountersBundle\Command\CurrentQueueUpdateCommand;

/**
 * QueueController
 *
 * @package EncountersBundle
 */
class QueueController extends Controller {

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
        $Mamba = $this->get('mamba');
        if (!$Mamba->getReady()) {
            list($this->json['status'], $this->json['message']) = array(1, "Mamba is not ready");
        }

        $Redis = $this->get('redis');
        if ($Redis->zSize(sprintf(EncountersBundle::REDIS_ZSET_USER_CURRENT_QUEUE_KEY, $Mamba->get('oid'))) &&
            $currentQueue = $Redis->zRange(sprintf(EncountersBundle::REDIS_ZSET_USER_CURRENT_QUEUE_KEY, $Mamba->get('oid')), 0, array_sum(CurrentQueueUpdateCommand::$balance))) {

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
                $this->json['data'][$key]['photos'] = $dataArray['photos'];
            }

        } else {
            list($this->json['status'], $this->json['message']) = array(2, "Current queue is not ready");

            $this->get('gearman')->getClient()
                ->doHighBackground(EncountersBundle::GEARMAN_CURRENT_QUEUE_UPDATE_FUNCTION_NAME, $Mamba->get('oid'));
        }

        return new Response(json_encode($this->json), 200, array("application/json"));
    }
}