<?php
namespace Mamba\EncountersBundle\Controller;
use Symfony\Component\HttpFoundation\Response;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Mamba\EncountersBundle\Helpers\Popularity;

/**
 * AdminEnergyController
 *
 * @package EncountersBundle
 */
class AdminEnergyController extends ApplicationController {

    /**
     * Index action
     *
     * @param int $user_id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($user_id) {
        $userId = intval($user_id);

        if ($this->getRequest()->getMethod() == 'POST' && is_numeric($energy = $this->getRequest()->request->get('energy'))) {
            $energy = intval($energy);
            if ($energy && $energy <= max(Popularity::$levels))  {
                $this->getEnergyObject()->set($userId, $energy);
            }
        }

        $dataArray = array(
            'user' => array(
                'id'     => $userId,
                'energy' => $this->getEnergyObject()->get($userId),
            ),
        );

        return $this->render('EncountersBundle:templates:admin.energy.html.twig', $dataArray);
    }
}