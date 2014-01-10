<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Symfony\Component\HttpFoundation\Response;
use Core\MambaBundle\API\Mamba;
use Mamba\EncountersBundle\EncountersBundle;

/**
 * PlatformSpamController
 *
 * @package EncountersBundle
 */
class PlatformSpamController extends ApplicationController {

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
     * Save action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function saveAction() {
        if ($webUserId = $this->getSession()->get(Mamba::SESSION_USER_ID_KEY)) {
            $ids  = $this->getRequest()->request->get('ids');
            
        } else {
            list($this->json['status'], $this->json['message']) = array(1, "Invalid session");
        }

        return $this->JSONResponse($this->json);
    }
}