<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Symfony\Component\HttpFoundation\Response;
use Core\MambaBundle\API\Mamba;
use Mamba\EncountersBundle\Tools\Gifts\Gifts;

/**
 * GiftsController
 *
 * @package EncountersBundle
 */
class GiftsController extends ApplicationController {

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
     * Gift purchaser
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function purchaseAction() {
        if ($webUserId = (int) $this->getSession()->get(Mamba::SESSION_USER_ID_KEY)) {
            $currentUserId = (int) $this->getRequest()->request->get('current_user_id');
            $giftId = (int) $this->getRequest()->request->get('gift_id');
            $comment = (string) $this->getRequest()->request->get('comment');

            if ($currentUserId && $giftId && ($Gift = \Mamba\EncountersBundle\Tools\Gifts\Gifts::getInstance()->getGiftById($giftId))) {

                $Account = $this->getAccountObject();
                $account = $Account->get($webUserId);

                $cost = $Gift->getCost();
                if ($account >= $cost) {
                    $account = $Account->decr($webUserId, $cost);

                    $this->getGiftsObject()->add($webUserId, $currentUserId, $giftId, $comment);

                    $userInfo = $this->getMamba()->Anketa()->getInfo($webUserId);

                    $this->json['data'] = array(
                        'account' => $account,
                        'gift'    => array(
                            'url' => $Gift->getUrl(),
                            'comment' => $comment,
                            'sender' => array(
                                'user_id' => $userInfo[0]['info']['oid'],
                                'name' => $userInfo[0]['info']['name'],
                                'age' => $userInfo[0]['info']['age'],
                                'city' => $userInfo[0]['location']['city'],
                            ),
                        ),
                    );
                } else {
                    list($this->json['status'], $this->json['message']) = array(3, "Account is not enough for charge battery");
                }
            } else {
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