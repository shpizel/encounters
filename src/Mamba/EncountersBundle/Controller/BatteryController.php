<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Symfony\Component\HttpFoundation\Response;
use Core\MambaBundle\API\Mamba;
use Mamba\EncountersBundle\Helpers\Battery;
use Mamba\EncountersBundle\EncountersBundle;

/**
 * BatteryController
 *
 * @package EncountersBundle
 */
class BatteryController extends ApplicationController {

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
     * Battery charger
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function chargeAction() {
        if ($webUserId = (int) $this->getSession()->get(Mamba::SESSION_USER_ID_KEY)) {
            $Account = $this->getAccountHelper();
            $Battery = $this->getBatteryHelper();

            $charge  = $Battery->get($webUserId);
            $account = $Account->get($webUserId);
            $cost    = (Battery::MAXIMUM_CHARGE - $charge)*2;

            if ($cost) {
                if ($account >= $cost) {
                    $account = $Account->decr($webUserId, $cost);
                    $Battery->set($webUserId, Battery::MAXIMUM_CHARGE);

                    $this->json['data'] = array(
                        'account' => $account
                    );
                } else {
                    list($this->json['status'], $this->json['message']) = array(3, "Account is not enough for charge battery");
                }
            } else {
                list($this->json['status'], $this->json['message']) = array(2, "Battery charged");
            }
        } else {
            list($this->json['status'], $this->json['message']) = array(1, "Invalid session");
        }

        return $this->JSONResponse($this->json);
    }
}