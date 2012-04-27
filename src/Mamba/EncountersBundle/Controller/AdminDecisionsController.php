<?php
namespace Mamba\EncountersBundle\Controller;
use Symfony\Component\HttpFoundation\Response;

use Mamba\EncountersBundle\Controller\ApplicationController;
use PDO;

/**
 * AdminDecisionsController
 *
 * @package EncountersBundle
 */
class AdminDecisionsController extends ApplicationController {

//    const
//
//        /**
//         * SQL-запрос на получение текущей информации по кешу
//         *
//         * @var string
//         */
//        GET_DECISIONS_STATS_SQL = "
//            SELECT
//                date_format(changed, '%d.%m.%y') as `date`,
//                sum(if(decision = -1, 1, 0)) as `NO`,
//                sum(if(decision = 0, 1, 0))  as `MAYBE`,
//                sum(if(decision = 1, 1, 0))  as `YES`,
//                count(*) as `TOTAL`
//            FROM
//                `Decisions`
//            WHERE
//                `changed` >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL %LIMIT% DAY), '%Y-%m-%d 00:00:00')
//            GROUP BY
//                `date`
//            ORDER BY
//                `changed` DESC
//            LIMIT
//                %LIMIT%
//        "
//    ;

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
            foreach ($data as $key=>$item) {
                $item['decision_no'] = intval(isset($item['decision_no']) ? $item['decision_no'] : 0);
                $item['decision_maybe'] = intval(isset($item['decision_maybe']) ? $item['decision_maybe'] : 0);
                $item['decision_yes'] = intval(isset($item['decision_yes']) ? $item['decision_yes'] : 0);
                $item['date'] = date("Y-m-d", strtotime("-$key day"));

                $dataArray['items'][] = $item;

                $dataArray['info']['no'] += $item['decision_no'];
                $dataArray['info']['maybe'] += $item['decision_maybe'];
                $dataArray['info']['yes'] += $item['decision_yes'];
                $dataArray['info']['total'] += $item['decision_no'] + $item['decision_maybe'] + $item['decision_yes'];
            }
        }

        return $this->render('EncountersBundle:templates:admin.decisions.html.twig', $dataArray);
    }
}