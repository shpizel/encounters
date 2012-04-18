<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;

/**
 * AdminBatteryController
 *
 * @package EncountersBundle
 */
class AdminBatteryController extends ApplicationController {

    /**
     * Index action
     *
     * @param int $user_id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($user_id) {
        $userId = intval($user_id);

        if ($this->getRequest()->getMethod() == 'POST' && is_numeric($battery = $this->getRequest()->request->get('battery'))) {
            $battery = intval($battery);
            if ($battery && $battery <= 5)  {
                $this->getBatteryObject()->set($userId, $battery);
            }
        }

        $dataArray = array(
            'user' => array(
                'id'      => $userId,
                'battery' => $this->getBatteryObject()->get($userId),
            ),
        );

        return $this->render('EncountersBundle:templates:admin.battery.html.twig', $dataArray);
    }
}