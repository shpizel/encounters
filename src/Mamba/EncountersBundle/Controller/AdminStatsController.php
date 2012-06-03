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
    public function indexAction($limit = 10) {
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
            foreach ($data as $key=>$item) {

                if (isset($item['decision_yes']) && isset($item['decision_no']) && isset($item['decision_maybe'])) {
                    $item['decision_total'] = intval($item['decision_yes']) + intval($item['decision_no']) + intval($item['decision_maybe']);
                }

                foreach (array('achievement', 'contacts', 'notify', 'decision_no', 'decision_yes', 'decision_maybe', 'decision_total') as $_key) {
                    if (!isset($item[$_key])) {
                        $item[$_key] = null;
                    }
                }

                $dataArray['items'][] = array(
                    'date' => date('Y-m-d', strtotime("-$key day")),
                    'item' => $item,
                );
            }
        }

        return $this->render('EncountersBundle:templates:admin.stats.html.twig', $dataArray);
    }
}