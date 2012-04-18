<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use PDO;

/**
 * AdminUserController
 *
 * @package EncountersBundle
 */
class AdminUserController extends ApplicationController {

    /**
     * Index action
     *
     * @param int $user_id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($user_id) {
        $userId = intval($user_id);

        $dataArray = array(
            'billing'   => array(),
            'variables' => array(),
            'notifications' => array(),
            'queues' => array(
                'current'  => $this->getCurrentQueueObject()->getAll($userId),
                'search'   => $this->getSearchQueueObject()->getSize($userId),
                'contacts' => $this->getContactsQueueObject()->getSize($userId),
                'hitlist'  => $this->getHitlistQueueObject()->getSize($userId),
                'priority' => $this->getPriorityQueueObject()->getSize($userId),
            )
        );
        $dataArray['platform_settings'] = $platformSettings = $this->getPlatformSettingsObject()->get($userId);
        $dataArray['search_preferences'] = $searchPreferences = $this->getSearchPreferencesObject()->get($userId);
        if ($notifications = $this->getNotificationsObject()->getAll($userId)) {
            foreach ($notifications as &$notification) {
                $notification = json_decode($notification, true);
            }

            $dataArray['notifications'] = $notifications;
        }

        if ($variables = $this->getVariablesObject()->getAll($userId)) {
            foreach ($variables as &$data) {
                $data = json_decode($data, true);
            }

            $dataArray['variables'] = $variables;
        }

        $dataArray['counters'] = $counters = $this->getCountersObject()->getAll($userId);

        $dataArray['user'] = array(
            'id'      => $userId,
            'energy'  => $this->getEnergyObject()->get($userId),
            'level'   => $this->getPopularityObject()->getLevel($this->getEnergyObject()->get($userId)),
            'battery' => $this->getBatteryObject()->get($userId),
        );

        $stmt = $this->getDoctrine()->getConnection()->prepare("select * from Billing where user_id = :user_id order by changed desc");
        $stmt->bindParam('user_id', $userId);
        if ($stmt->execute()) {
            while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $dataArray['billing'][] = $item;
            }
        }

        return $this->render('EncountersBundle:templates:admin.user.html.twig', $dataArray);
    }
}