<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Mamba\EncountersBundle\Helpers\Popularity;
use PDO;

/**
 * AdminUsersController
 *
 * @package EncountersBundle
 */
class AdminUsersController extends ApplicationController {

    /**
     * Index action
     *
     * @param int $user_id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($user_id) {
        if ($this->getRequest()->getMethod() == 'POST' && is_numeric($userId = $this->getRequest()->request->get('user_id'))) {
            return new \Symfony\Component\HttpFoundation\RedirectResponse("users/{$userId}");
        } else {
            $userId = intval($user_id);
        }

        if ($userId) {
            $dataArray = array(
                'billing'   => array(),
                'variables' => array(),
                'notifications' => array(),
                'queues' => array(
                    'current'  => $this->getCurrentQueueHelper()->getAll($userId),
                    'search'   => $this->getSearchQueueHelper()->getSize($userId),
                    'contacts' => $this->getContactsQueueHelper()->getSize($userId),
                    'hitlist'  => $this->getHitlistQueueHelper()->getSize($userId),
                    'priority' => $this->getPriorityQueueHelper()->getSize($userId),
                )
            );

            if ($this->getRequest()->getMethod() == 'POST' && in_array($action = $this->getRequest()->request->get('action'), array('saveEnergy', 'saveBattery', 'saveAccount'))) {
                $dataArray['action'] = array(
                    'action' => $action,
                    'result' => false,
                );

                if ($action == 'saveEnergy' && is_numeric($energy = $this->getRequest()->request->get('energy'))) {
                    $energy = intval($energy);
                    if ($energy >= 0 && $energy <= max(Popularity::$levels))  {
                        $this->getEnergyHelper()->set($userId, $energy);
                        $dataArray['action']['result'] = true;
                    }
                } elseif ($action == 'saveBattery' && is_numeric($battery = $this->getRequest()->request->get('battery'))) {
                    $battery = intval($battery);
                    if ($battery >= 0 && $battery <= 5)  {
                        $this->getBatteryHelper()->set($userId, $battery);
                        $dataArray['action']['result'] = true;
                    }
                } elseif ($action == 'saveAccount' && is_numeric($account = $this->getRequest()->request->get('account'))) {
                    $account = intval($account);
                    if ($account >= 0)  {
                        $this->getAccountHelper()->set($userId, $account);
                        $dataArray['action']['result'] = true;
                    }
                }
            }

            $dataArray['platform_settings'] = $platformSettings = $this->getPlatformSettingsHelper()->get($userId);
            $dataArray['search_preferences'] = $searchPreferences = $this->getSearchPreferencesHelper()->get($userId);
            if ($notifications = $this->getNotificationsHelper()->getAll($userId)) {
                foreach ($notifications as &$notification) {
                    $notification = json_decode($notification, true);
                }

                $dataArray['notifications'] = $notifications;
            }

            if ($variables = $this->getVariablesHelper()->getAll($userId)) {
                foreach ($variables as &$data) {
                    $data = json_decode($data, true);
                }

                $dataArray['variables'] = $variables;
            }

            $dataArray['counters'] = $counters = $this->getCountersHelper()->getAll($userId);

            $dataArray['user'] = array(
                'id'      => $userId,
                'energy'  => $this->getEnergyHelper()->get($userId),
                'level'   => $this->getPopularityHelper()->getLevel($this->getEnergyHelper()->get($userId)),
                'battery' => $this->getBatteryHelper()->get($userId),
                'account' => $this->getAccountHelper()->get($userId),
            );

            $userInfo = $this->getUsersHelper($userId)[$userId];

            $dataArray['user']['info'] = $userInfo;
            if ($dataArray['user']['info_dump'] = $userInfo) {
                foreach ($dataArray['user']['info_dump'] as &$var) {
                    $var = var_export($var, true);
                }
            }

            $stmt = $this->getDoctrine()->getConnection()->prepare("select * from Billing where user_id = :user_id order by changed desc");
            $stmt->bindParam('user_id', $userId);
            if ($stmt->execute()) {
                while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $dataArray['billing'][] = $item;
                }
            }
        } else {
            $dataArray['user'] = array('id' => $userId);
        }

        $dataArray['controller'] = $this->getControllerName(__CLASS__);

        return $this->TwigResponse('EncountersBundle:templates:admin.users.html.twig', $dataArray);
    }
}