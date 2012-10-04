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
        return $this->render('EncountersBundle:templates:admin.html.twig', array(
            'controller' => __CLASS__
        ));
    }
}