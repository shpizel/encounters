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
            if ($ids = array_map(function($item){return (int)$item;}, $this->getRequest()->request->get('ids'))) {
                $limit = (int) $this->getRequest()->request->get('limit');

                $this->getStatsHelper()->incr("mambaspam", count($ids));
                $this->getRedis()->hIncrBy("mambaspam-by-{$webUserId}", date("dmy"), count($ids));

                foreach ($ids as $id) {
                    $this->getRedis()->lRem("spamqueue-by-{$webUserId}", $id, 0);
                }

            } else {
                list($this->json['status'], $this->json['message']) = array(2, "Invalid params");
            }
        } else {
            list($this->json['status'], $this->json['message']) = array(1, "Invalid session");
        }

        return $this->JSONResponse($this->json);
    }
}