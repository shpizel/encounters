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

    const

        /**
         * SQL-запрос на получение текущей информации по кешу
         *
         * @var string
         */
        GET_DECISIONS_STATS_SQL = "
            SELECT
                date_format(changed, '%d.%m.%y') as `date`,
                sum(if(decision = -1, 1, 0)) as `NO`,
                sum(if(decision = 0, 1, 0))  as `MAYBE`,
                sum(if(decision = 1, 1, 0))  as `YES`,
                count(*) as `TOTAL`
            FROM
                `Decisions2`

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

        $stmt = $this->getDoctrine()->getConnection()->prepare(
            str_replace("%LIMIT%", $limit, self::GET_DECISIONS_STATS_SQL)
        );

        if ($stmt->execute()) {
            while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $dataArray['items'][] = $item;

                $dataArray['info']['no'] += $item['NO'];
                $dataArray['info']['maybe'] += $item['MAYBE'];
                $dataArray['info']['yes'] += $item['YES'];
                $dataArray['info']['total'] += $item['TOTAL'];

            }
        }

        return $this->render('EncountersBundle:templates:admin.decisions.html.twig', $dataArray);
    }
}