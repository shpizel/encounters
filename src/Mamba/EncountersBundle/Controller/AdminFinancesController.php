<?php
namespace Mamba\EncountersBundle\Controller;
use Symfony\Component\HttpFoundation\Response;

use Mamba\EncountersBundle\Controller\ApplicationController;
use PDO;

/**
 * AdminFinancesController
 *
 * @package EncountersBundle
 */
class AdminFinancesController extends ApplicationController {

    const

        /**
         * SQL-запрос на получение информации по финансам с группировкой по датам
         *
         * @var string
         */
        SQL_GET_FINANCES_STATS = "
            SELECT
                round(sum(if(date_format(`changed`, '%H%i') <= date_format(now(), '%H%i'), amount_developer, 0)),2) as `current`,
                round(sum(amount_developer), 2) as `daily`,
                date_format(`changed`, '%Y-%m-%d') as `date`
            FROM
                Billing
            WHERE
                `date` >= date_format(DATE_SUB(NOW(), INTERVAL %LIMIT% DAY), '%Y-%m-%d 00:00:00')
            GROUP BY
                `date`
            ORDER BY
                `changed` DESC
            LIMIT
                %LIMIT%
        ",

        /**
         * SQL-запрос на получение информации по количеству вложенных монет
         *
         * @var string
         */
        SQL_GET_AMOUNT_STATS = "
            SELECT
                sum(if(amount = 1, 1, 0)) as `1`,
                sum(if(amount = 2, 1, 0)) as `2`,
                sum(if(amount = 5, 1, 0)) as `5`,
                sum(if(amount = 10, 1, 0)) as `10`,
                sum(if(amount = 25, 1, 0)) as `25`,
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
            str_replace("%LIMIT%", $limit, self::SQL_GET_FINANCES_STATS)
        );

        if ($stmt->execute()) {
            while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $item['ts'] = strtotime($item['date']);
                $dataArray['items'][] = $item;
                $dataArray['info']['sum'] += $item['daily'];
            }
        }

        $dataArray['controller'] = $this->getControllerName(__CLASS__);

        return $this->TwigResponse('EncountersBundle:templates:admin.finances.html.twig', $dataArray);
    }
}