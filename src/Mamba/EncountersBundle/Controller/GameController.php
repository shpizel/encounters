<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Mamba\PlatformBundle\API\Mamba;

/**
 * GameController
 *
 * @package EncountersBundle
 */
class GameController extends ApplicationController {

    /**
     * Index action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {
        $Mamba = $this->get('Mamba');
        if (!$Mamba->getReady()) {
            return $this->redirect($this->generateUrl('welcome'));
        }

        return $this->render("EncountersBundle:Game:game.html.twig");
    }
}