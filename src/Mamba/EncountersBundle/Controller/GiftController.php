<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Symfony\Component\HttpFoundation\Response;
use Core\MambaBundle\API\Mamba;
use Mamba\EncountersBundle\EncountersBundle;

/**
 * GiftController
 *
 * @package EncountersBundle
 */
class GiftController extends ApplicationController {

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
     * Everyday gift getter
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getEveryDayGiftAction() {
        if ($webUserId = (int) $this->getSession()->get(Mamba::SESSION_USER_ID_KEY)) {
            $Variables = $this->getVariablesObject();
            $last_everyday_gift_accepted = $Variables->get($webUserId, 'last_everyday_gift_accepted');
            $last_everyday_gift_accepted_counter = $Variables->get($webUserId, 'last_everyday_gift_accepted_counter');

            if (time() - $last_everyday_gift_accepted > 24*3600) {
                // чувак пропустил целые сутки
                $last_everyday_gift_accepted_counter = 0;
                $Variables->set($webUserId, 'last_everyday_gift_accepted_counter', $last_everyday_gift_accepted_counter);
            }

            if ((time() > $last_everyday_gift_accepted) && (date("d") != date("d", $last_everyday_gift_accepted))) {
                $last_everyday_gift_accepted_counter++;
                if ($last_everyday_gift_accepted_counter > 5) {
                    $last_everyday_gift_accepted_counter = 5;
                }

                $account = $this->getAccountObject()->incr($webUserId, $last_everyday_gift_accepted_counter);
                $Variables->set($webUserId, 'last_everyday_gift_accepted_counter', $last_everyday_gift_accepted_counter);
                $Variables->set($webUserId, 'last_everyday_gift_accepted', time());

                $this->json['data'] = array('account' => $account);
            } else {
                list($this->json['status'], $this->json['message']) = array(2, "Cheat attempt detected  ");
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