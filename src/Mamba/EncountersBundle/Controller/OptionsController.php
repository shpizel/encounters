<?php
namespace Mamba\EncountersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Mamba\PlatformBundle\API\Mamba;

/**
 * BillingController
 *
 * @package EncountersBundle
 */
class BillingController extends Controller {

    protected

        /**
         * Параметры, передаваемые биллинг-скрипту
         *
         * @var array
         */
        $requiredParams = array(
            'app_id', // 1 - номер приложения 
            'oid', // 173952510 - идентификатор анкеты платящего 
            'operation_id', // 101914189 - номер операции (транзакции). уникален для каждой оплаты пользователя приложения. 
            'amount', // 2.00000 - количество внесенных денег. 
            'amount_developer', // 1.14000 - комиссия разработчика с платежа. 
            'validation_id', // 132660758 - индитификатор для сверки. 
            'time', // unixts- время оплаты. 
            'sig', // 6c5cb969eb69115daa029545ee23d4c5 - подпись данных. 
        )
    ;

    public function indexAction() {
        $Request  = $this->getRequest();
        $getParams = $Request->query->all();
        if (count(array_intersect(array_keys($getParams), $this->requiredParams)) == count($this->requiredParams)) {
            $billingParams = array();
            foreach ($this->requiredParams as $requiredParam) {
                $billingParams[$requiredParam] = $getParams[$requiredParam];
            }

            /**
             * @todo BL
             * @author shpizel
             */
        }
    }
}