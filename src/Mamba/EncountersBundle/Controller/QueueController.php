<?php
namespace Mamba\EncountersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Mamba\PlatformBundle\API\Mamba;
use Mamba\EncountersBundle\EncountersBundle;

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

        return new Response(json_encode($this->json), 200, array("application/json"));
    }
}