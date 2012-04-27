<?php
namespace Mamba\EncountersBundle\Controller;
use Symfony\Component\HttpFoundation\Response;

use Mamba\EncountersBundle\Controller\ApplicationController;
use PDO;

/**
 * AdminStatsController
 *
 * @package EncountersBundle
 */
class AdminStatsController extends ApplicationController {

    /**
     * Index action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($limit = 7) {
        $dataArray = array(
            'items' => array(),
            'info'  => array(
                'limit' => $limit,
                'no'    => 0,
                'maybe' => 0,
                'yes'   => 0,
                'total' => 0,
            ),
        );

        $Redis = $this->getRedis();
        $Redis->multi();
        foreach (range(0, $limit) as $day) {
            $Redis->hGetAll("stats_by_" . ($date = date("dmy", strtotime("-$day day"))));
        }

        if ($data = $Redis->exec()) {
            foreach ($data as $key=>$item) {;
                $dataArray['items'][] = array(
                    'date' => date('Y-m-d', strtotime("-$key day")),
                    'item' => $item,
                );
            }
        }

        return $this->render('EncountersBundle:templates:admin.stats.html.twig', $dataArray);
    }
}