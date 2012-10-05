<?php
namespace Mamba\EncountersBundle\Controller;
use Symfony\Component\HttpFoundation\Response;

use Mamba\EncountersBundle\Controller\ApplicationController;

/**
 * AdminMemcacheController
 *
 * @package EncountersBundle
 */
class AdminMemcacheController extends ApplicationController {

    /**
     * Index action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {
        $stats = $this->getMemcache()->getStats();

        $dataArray['items'] = $stats;
        $dataArray['keys'] = array_keys($stats);
        $dataArray['controller'] = $this->getControllerName(__CLASS__);

        return $this->render('EncountersBundle:templates:admin.memcache.html.twig', $dataArray);
    }
}