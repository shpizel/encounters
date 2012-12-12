<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Symfony\Component\HttpFoundation\Response;
use Core\MambaBundle\API\Mamba;
use Mamba\EncountersBundle\Helpers\Battery;
use Mamba\EncountersBundle\EncountersBundle;

/**
 * LevelController
 *
 * @package EncountersBundle
 */
class LevelController extends ApplicationController {

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
     * Level up action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function upAction() {
        if ($webUserId = (int) $this->getSession()->get(Mamba::SESSION_USER_ID_KEY)) {
            $Account = $this->getAccountObject();

            $account = $Account->get($webUserId);
            $level   = $this->getPopularityObject()->getLevel($this->getEnergyObject()->get($webUserId));

            if ($level >= 4 && $level < 16) {
                $cost = ($level + 1 - 4)*10;
                if ($account >= $cost) {
                    $account = $Account->decr($webUserId, $cost);
                    $this->getEnergyObject()->set($webUserId, $this->getPopularityObject()->getLevels()[$level + 1]);
                    $this->json['data'] = array(
                        'popularity' => $this->getPopularityObject()->getInfo($this->getEnergyObject()->get($webUserId)),
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

        return
            new Response(json_encode($this->json), 200, array(
                "content-type" => "application/json",
                )
            )
        ;
    }
}