<?php
namespace Mamba\EncountersBundle\Controller;
use Symfony\Component\HttpFoundation\Response;

use Mamba\EncountersBundle\Controller\ApplicationController;

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
        GET_CASH_SQL = "
            SELECT
                round(sum(if(date_format(`changed`, '%H%i') <= date_format(now(), '%H%i'), amount_developer, 0)),2) as `current`,
                round(sum(amount_developer), 2) as `daily`,
                date_format(`changed`, '%d.%m.%y') as `date`
            FROM
                Billing
            GROUP BY
                `date`
            ORDER BY
                `changed` DESC
            LIMIT
                10
        "
    ;

    /**
     * Index action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {
        return new Response("cash");
    }
}