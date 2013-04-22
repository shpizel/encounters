<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;

/**
 * AdminController
 *
 * @package EncountersBundle
 */
class AdminController extends ApplicationController {

    /**
     * Index action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {
        return
            $this->TwigResponse(
                'EncountersBundle:templates:admin.html.twig',
                array(
                    'controller' => $this->getControllerName(__CLASS__),
                )
            )
        ;
    }
}