<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Core\MambaBundle\API\Mamba;
use Mamba\EncountersBundle\Helpers\Gifts;
use Mamba\EncountersBundle\Helpers\Photoline;

/**
 * ProfileController
 *
 * @package EncountersBundle
 */
class ProfileController extends ApplicationController {

    /**
     * Index action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {
        $Mamba = $this->getMamba();
        if (!$Mamba->getReady()) {
            return $this->redirect($this->generateUrl('welcome'));
        }

        if (!$this->getSearchPreferencesHelper()->get($webUserId = $Mamba->getWebUserId())) {
            return $this->redirect($this->generateUrl('welcome'));
        }

        /** Определяем user_id */
        if (!($currentUserId = (int) $this->getRequest()->query->get('id'))) {
            $currentUserId = (int) $webUserId;
        }

        if ($webUserId == $currentUserId) {
            $this->getCountersHelper()->set($webUserId, 'events_unread', 0);
        }

        if (!($profile = $Mamba->Anketa()->getInfo($currentUserId))) {
            return $this->redirect($this->generateUrl('welcome'));
        }

        $dataArray = $this->getInitialData();

        $dataArray['profile'] = $profile[0];
        if (!($dataArray['profile']['myself'] = $currentUserId == $webUserId)) {
            $dataArray['profile']['rated'] = $this->getViewedQueueHelper()->exists($webUserId, $currentUserId);
        }

        $dataArray['profile']['photos'] = $Mamba->Photos()->get($currentUserId)['photos'];

        /** перемешаем интересы */
        if (isset($dataArray['profile']['interests'])) {
            shuffle($dataArray['profile']['interests']);
        }

        if ($dataArray['profile']['gifts'] = $this->getGiftsHelper()->get($currentUserId)) {
            $userData = array();
            foreach ($this->getMamba()->Anketa()->getInfo(array_unique(array_map(function($item) {
                return (int) $item['web_user_id'];
            }, $dataArray['profile']['gifts']))) as $userInfo) {
                $userData[$userInfo['info']['oid']] = $userInfo;
            }

            foreach ($dataArray['profile']['gifts'] as $giftKey=>$giftData) {
                $dataArray['profile']['gifts'][$giftKey]['gift'] = \Mamba\EncountersBundle\Tools\Gifts\Gifts::getInstance()->getGiftById($giftData['gift_id'])->toArray();
                $dataArray['profile']['gifts'][$giftKey]['sender'] = array(
                    'name' => $userData[$giftData['web_user_id']]['info']['name'],
                    'age' => $userData[$giftData['web_user_id']]['info']['age'],
                    'city' => $userData[$giftData['web_user_id']]['location']['city'],
                );
            }
        }

        $dataArray['current_user_id'] = $currentUserId;
        $dataArray['purchased'] = $this->getPurchasedHelper()->exists($webUserId, $currentUserId);

        $this->getStatsHelper()->incr('profile-hits');

        return $this->TwigResponse("EncountersBundle:templates:profile.html.twig", $dataArray);
    }
}