<?php
namespace Mamba\EncountersBundle\Controller;

use Symfony\Component\HttpFoundation\Response;

use Mamba\EncountersBundle\Controller\ApplicationController;

/**
 * AdminPlatformController
 *
 * @package EncountersBundle
 */
class AdminPlatformController extends ApplicationController {

    /**
     * Index action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($limit = 10) {
        $dataArray = array(
            'items' => array(),
            'info'  => array(
                'limit' => $limit,
            ),
        );

        $Redis = $this->getRedis();
        $Redis->multi();
        foreach (range(0, $limit - 1) as $day) {
            $Redis->hGetAll("mamba-platform-execution-frequently-" . ($date = date("dmy", strtotime("-$day day"))));
        }

        if ($data = $Redis->exec()) {
            foreach ($data as $key=>$item) {
                $item['date'] = date("Y-m-d", $item['ts'] = strtotime("-$key day"));

                $dataArray['items'][] = $item;
            }
        }

        $dataArray['controller'] = $this->getControllerName(__CLASS__);

        return $this->TwigResponse('EncountersBundle:templates:admin.platform.html.twig', $dataArray);
    }
}