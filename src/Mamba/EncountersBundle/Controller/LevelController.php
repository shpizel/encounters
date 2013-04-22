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
            $Account = $this->getAccountHelper();

            $account = $Account->get($webUserId);
            $level   = $this->getPopularityHelper()->getLevel($this->getEnergyHelper()->get($webUserId));

            if ($level >= 4 && $level < 16) {
                $cost = ($level + 1 - 4)*10;
                if ($account >= $cost) {
                    $account = $Account->decr($webUserId, $cost);
                    $this->getEnergyHelper()->set($webUserId, $this->getPopularityHelper()->getLevels()[$level + 1]);
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