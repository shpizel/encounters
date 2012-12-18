<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Symfony\Component\HttpFoundation\Response;
use Core\MambaBundle\API\Mamba;
use Mamba\EncountersBundle\EncountersBundle;

/**
 * PhotolineController
 *
 * @package EncountersBundle
 */
class PhotolineController extends ApplicationController {

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
     * Photoline getter
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction() {
        $Mamba = $this->getMamba();

        if ($webUserId = (int) $this->getSession()->get(Mamba::SESSION_USER_ID_KEY)) {
            $webUser = $Mamba->Anketa()->getInfo($webUserId);

            if ($photolineItems = $this->getPhotolineObject()->get($webUser[0]['location']['region_id'])) {
                $photoLinePhotos = $Mamba->Anketa()->getInfo($photolineIds = array_map(function($item) {
                    return (int) $item['user_id'];
                }, $photolineItems), array('location'));

                $photoline = array();
                foreach ($photolineIds as $userId) {
                    foreach ($photoLinePhotos as $photoLinePhotosItem) {
                        if ($photoLinePhotosItem['info']['oid'] == $userId) {
                            if ($photoLinePhotosItem['info']['square_photo_url']) {
                                $photoline[] = array(
                                    'user_id'   => $userId,

                                    'name'      => $photoLinePhotosItem['info']['name'],
                                    'age'       => $photoLinePhotosItem['info']['age'],
                                    'city'      => $photoLinePhotosItem['location']['city'],

                                    'photo_url' => $photoLinePhotosItem['info']['square_photo_url'],
                                );
                            }
                        }
                    }
                }
            } else {
                $photoline = array();
            }

            $this->json['data'] = array(
                'items' => $photoline,
            );

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

    /**
     * Photoline purchaser
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function purchaseAction() {
        if ($webUserId = (int) $this->getSession()->get(Mamba::SESSION_USER_ID_KEY)) {
            $Account = $this->getAccountObject();
            $account = $Account->get($webUserId);

            $webUser = $this->getMamba()->Anketa()->getInfo($webUserId);

            $cost = 1;
            if ($account >= $cost) {
                $account = $Account->decr($webUserId, $cost);
                $this->getPhotolineObject()->add($webUser[0]['location']['region_id'], $webUserId);

                $this->json['data'] = array(
                    'account' => $account
                );
            } else {
                list($this->json['status'], $this->json['message']) = array(3, "Account is not enough for charge battery");
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

    /**
     * Photoline chooser
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function chooseAction() {
        if ($webUserId = (int) $this->getSession()->get(Mamba::SESSION_USER_ID_KEY)) {

            $this->getStatsObject()->incr('photoline-click');

            if ($currentUserId = (int) $this->getRequest()->request->get('user_id')) {
                if (!$this->getViewedQueueObject()->exists($webUserId, $currentUserId)) {
                    if ($webUserId != $currentUserId) {
                        $this->getCurrentQueueObject()->put($webUserId, $currentUserId);
                    } else {
                        list($this->json['status'], $this->json['message']) = array(4, "Its me");
                    }
                } else {
                    list($this->json['status'], $this->json['message']) = array(3, "Already voted");
                }
            } else {
                list($this->json['status'], $this->json['message']) = array(2, "Invalid input data");
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