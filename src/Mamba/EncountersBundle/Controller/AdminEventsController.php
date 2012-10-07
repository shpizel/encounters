<?php
namespace Mamba\EncountersBundle\Controller;
use Symfony\Component\HttpFoundation\Response;

use Mamba\EncountersBundle\Controller\ApplicationController;
use PDO;

/**
 * AdminEventsController
 *
 * @package EncountersBundle
 */
class AdminEventsController extends ApplicationController {

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
                'achievement' => 0,
                'contacts' => 0,
                'notify' => 0,
            ),
        );

        $Redis = $this->getRedis();
        $Redis->multi();
        foreach (range(1, $limit) as $day) {
            $Redis->hGetAll("stats_by_" . ($date = date("dmy", strtotime("-$day day"))));
        }

        if ($data = $Redis->exec()) {
            foreach ($data as $key=>$item) {
                foreach (array('achievement', 'contacts', 'notify') as $_key) {
                    if (!isset($item[$_key])) {
                        $item[$_key] = null;
                    } else {
                        $dataArray['info'][$_key] += $item[$_key];
                    }
                }

                $dataArray['items'][] = array(
                    'date' => date('Y-m-d', strtotime("-$key day")),
                    'ts'   => strtotime("-$key day"),
                    'item' => $item,
                );
            }
        }

        $dataArray['controller'] = $this->getControllerName(__CLASS__);

        return $this->render('EncountersBundle:templates:admin.events.html.twig', $dataArray);
    }
}