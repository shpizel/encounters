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
            'common' => array(
                'items' => array(),
                'info'  => array(
                    'achievement' => 0,
                    'notify'      => 0,

                    'limit' => $limit,
                ),
            ),

            'decision_get' => array(
                'items' => array(),
                'info'  => array(
                    'decision.get-battery.notrequired' => 0,
                    'decision.get-battery.charge'      => 0,
                    'decision.get-battery.decr'        => 0,
                    'decision.get-battery.empty'       => 0,

                    'limit' => $limit,
                ),
            )
        );

        $Redis = $this->getRedis();
        $Redis->multi();
        foreach (range(0, $limit - 1) as $day) {
            $Redis->hGetAll("stats_by_" . ($date = date("dmy", strtotime("-$day day"))));
        }

        if ($data = $Redis->exec()) {
            foreach ($data as $key=>$item) {
                foreach ($requiredKeys = array('achievement', 'notify') as $_key) {
                    if (!isset($item[$_key])) {
                        $item[$_key] = null;
                    } else {
                        $dataArray['common']['info'][$_key] += $item[$_key];
                    }
                }

                /** фильтруем item */
                foreach ($item as $ikey=>$val) {
                    if (!in_array($ikey, $requiredKeys)) {
                        unset($item[$ikey]);
                    }
                }

                $dataArray['common']['items'][] = array(
                    'date' => date('Y-m-d', strtotime("-$key day")),
                    'ts'   => strtotime("-$key day"),
                    'item' => $item,
                );
            }
        }

        $Redis->multi();
        foreach (range(0, $limit - 1) as $day) {
            $Redis->hGetAll("stats_by_" . ($date = date("dmy", strtotime("-$day day"))));
        }

        if ($data = $Redis->exec()) {
            foreach ($data as $key=>$item) {
                foreach ($requiredKeys = array('decision.get-battery.notrequired',
                             'decision.get-battery.charge',
                             'decision.get-battery.decr',
                             'decision.get-battery.empty') as $_key) {
                    if (!isset($item[$_key])) {
                        $item[$_key] = null;
                    } else {
                        $dataArray['decision_get']['info'][$_key] += $item[$_key];
                    }
                }

                /** фильтруем item */
                foreach ($item as $ikey=>$val) {
                    if (!in_array($ikey, $requiredKeys)) {
                        unset($item[$ikey]);
                    }
                }

                $dataArray['decision_get']['items'][] = array(
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