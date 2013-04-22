<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Symfony\Component\HttpFoundation\Response;
use Core\MambaBundle\API\Mamba;
use Mamba\EncountersBundle\Helpers\Battery;
use Mamba\EncountersBundle\EncountersBundle;

/**
 * PopularityController
 *
 * @package EncountersBundle
 */
class PopularityController extends ApplicationController {

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
     * Popularity getter
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction() {
        if ($webUserId = (int) $this->getSession()->get(Mamba::SESSION_USER_ID_KEY)) {
            $Account = $this->getAccountHelper();

            $account = $Account->get($webUserId);
            $level   = $this->getPopularityHelper()->getLevel($this->getEnergyHelper()->get($webUserId));

            if ($level < 4) {
                $cost = 30;
                if ($account >= $cost) {
                    $account = $Account->decr($webUserId, $cost);
                    $this->getEnergyHelper()->set($webUserId, $this->getPopularityHelper()->getLevels()[4]);

                    $this->json['data'] = array(
                        'popularity' => $this->getPopularityHelper()->getInfo($this->getEnergyHelper()->get($webUserId)),
                        'account'    => $account,
                    );
                } else {
                    list($this->json['status'], $this->json['message']) = array(3, "Account is not enough for level up");
                }
            } else {
                list($this->json['status'], $this->json['message']) = array(2, "Invalid level");
            }
        } else {
            list($this->json['status'], $this->json['message']) = array(1, "Invalid session");
        }

        return $this->JSONResponse($this->json);
    }
}