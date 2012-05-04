<?php
namespace Mamba\EncountersBundle\Controller;
use Symfony\Component\HttpFoundation\Response;

use Mamba\EncountersBundle\Controller\ApplicationController;
use PDO;

/**
 * AdminCashController
 *
 * @package EncountersBundle
 */
class AdminCashController extends ApplicationController {

    const

        /**
         * SQL-запрос на получение текущей информации по кешу
         *
         * @var string
         */
        GET_CASH_STATS_SQL = "
            SELECT
                round(sum(if(date_format(`changed`, '%H%i') <= date_format(now(), '%H%i'), amount_developer, 0)),2) as `current`,
                round(sum(amount_developer), 2) as `daily`,
                date_format(`changed`, '%Y-%m-%d') as `date`
            FROM
                Billing
            GROUP BY
                `date`
            ORDER BY
                `changed` DESC
            LIMIT
                %LIMIT%
        "
    ;

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
                'sum'   => 0,
            ),
        );

        $stmt = $this->getDoctrine()->getConnection()->prepare(
            str_replace("%LIMIT%", $limit, self::GET_CASH_STATS_SQL)
        );

        if ($stmt->execute()) {
            while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $item['ts'] = strtotime($item['date']);
                $dataArray['items'][] = $item;
                $dataArray['info']['sum'] += $item['daily'];
            }
        }

        return $this->render('EncountersBundle:templates:admin.cash.html.twig', $dataArray);
    }
}