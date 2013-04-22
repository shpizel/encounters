<?php
namespace Mamba\EncountersBundle\Controller;
use Symfony\Component\HttpFoundation\Response;

use Mamba\EncountersBundle\Controller\ApplicationController;

/**
 * AdminRedisController
 *
 * @package EncountersBundle
 */
class AdminRedisController extends ApplicationController {

    /**
     * Index action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {
        $nodes = $this->getRedis()->getNodes();
        $items = array();

        foreach ($nodes as $node) {
            if (!isset($items[$dsn = $node->getHost() . ":" . $node->getPort()])) {
                $items[$dsn] = $this->getRedis()->getNodeConnection($node)->info();
            }
        }

        $dataArray['items'] = $items;
        $dataArray['keys'] = array_keys($items);
        $dataArray['controller'] = $this->getControllerName(__CLASS__);

        return $this->TwigResponse('EncountersBundle:templates:admin.redis.html.twig', $dataArray);
    }
}