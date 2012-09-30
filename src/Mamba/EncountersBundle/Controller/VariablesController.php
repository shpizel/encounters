<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Symfony\Component\HttpFoundation\Response;
use Core\MambaBundle\API\Mamba;
use Mamba\EncountersBundle\EncountersBundle;

/**
 * VariablesController
 *
 * @package EncountersBundle
 */
class VariablesController extends ApplicationController {

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
     * Sets variables
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function setVariableAction() {
        if ($webUserId = $this->getSession()->get(Mamba::SESSION_USER_ID_KEY)) {
            $key  = $this->getRequest()->request->get('key');
            $data = $this->getRequest()->request->get('data');

            if (!$this->getVariablesObject()->set($webUserId, $key, $data)) {
                list($this->json['status'], $this->json['message']) = array(2, "Invalid params");
            }
        } else {
            list($this->json['status'], $this->json['message']) = array(1, "Invalid session");
        }

        return
            new Response(json_encode($this->json), 200, array(
                "content-type" => "application/json",
                )
            )
        ;
    }
}