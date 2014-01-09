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
            $webUser = $this->getUsersHelper()->getInfo($webUserId)[$webUserId];

            $from = null;
            if ($_from = $this->getRequest()->request->get('from')) {
                if ($_from = (float) $_from) {
                    $from = $_from;
                }

            }

            if
            (
                $photolineItems =
                    (
                        (!$from)
                            ? $this->getPhotolineHelper()->get($webUser['location']['region']['id'])
                            : $this->getPhotolineHelper()->getbyRange($webUser['location']['region']['id'], microtime(true), $from)
                    )
            ) {
                $photoLinePhotos = $this->getUsersHelper()->getInfo($photolineIds = array_map(function($item) {
                    return (int) $item['user_id'];
                }, $photolineItems));

                $photoline = array();
                $n = 0;
                foreach ($photolineIds as $userId) {
                    foreach ($photoLinePhotos as $photoLinePhotosItem) {
                        if ($photoLinePhotosItem['info']['user_id'] == $userId) {
                            if ($photoLinePhotosItem['avatar']['square_photo_url']) {
                                $photoline[] = array(
                                    'user_id'   => $userId,

                                    'name'      => $photoLinePhotosItem['info']['name'],
                                    'age'       => $photoLinePhotosItem['info']['age'],
                                    'city'      => $photoLinePhotosItem['location']['city']['name'],

                                    'photo_url' => $photoLinePhotosItem['avatar']['square_photo_url'],
                                    'comment'   => isset($photolineItems[$n]['comment']) ? htmlspecialchars($photolineItems[$n]['comment']) : null,
                                );
                            }

                            break;
                        }
                    }

                    $n++;
                }
            } else {
                $photoline = array();
            }

            $this->json['data'] = array(
                'items' => $photoline,
                'microtime' => microtime(true),
            );

        } else {
            list($this->json['status'], $this->json['message']) = array(1, "Invalid session");
        }

        return $this->JSONResponse($this->json);
    }

    /**
     * Photoline purchaser
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function purchaseAction() {
        if ($webUserId = (int) $this->getSession()->get(Mamba::SESSION_USER_ID_KEY)) {
            $Account = $this->getAccountHelper();
            $account = $Account->get($webUserId);

            $webUser = $this->getUsersHelper()->getInfo($webUserId)[$webUserId];

            $cost = 1;
            if ($account >= $cost) {
                $account = $Account->decr($webUserId, $cost);

                $this->getPhotolineHelper()->add($webUser['location']['region']['id'], $webUserId, $this->getRequest()->request->get('comment'));

                $this->json['data'] = array(
                    'account' => $account
                );
            } else {
                list($this->json['status'], $this->json['message']) = array(3, "Account is not enough for charge battery");
            }
        } else {
            list($this->json['status'], $this->json['message']) = array(1, "Invalid session");
        }

        return $this->JSONResponse($this->json);
    }

    /**
     * Photoline chooser
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function chooseAction() {
        if ($webUserId = (int) $this->getSession()->get(Mamba::SESSION_USER_ID_KEY)) {

            $this->getStatsHelper()->incr('photoline-click');

            if ($currentUserId = (int) $this->getRequest()->request->get('user_id')) {
                if (!$this->getViewedQueueHelper()->exists($webUserId, $currentUserId)) {
                    if ($webUserId != $currentUserId) {

                        $_data = $this->getUsersHelper()->getInfo($webUserId);
                        $_searchPreferences = $this->getSearchPreferencesHelper()->get($currentUserId);

                        if ($_data[$webUserId]['info']['gender'] == $_searchPreferences['gender']) {
                            $this->getCurrentQueueHelper()->put($webUserId, $currentUserId);
                        } else {
                            list($this->json['status'], $this->json['message']) = array(5, "Gender error");
                        }

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

        return $this->JSONResponse($this->json);
    }
}