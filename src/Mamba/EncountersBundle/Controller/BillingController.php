<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Helpers\Battery;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Core\MambaBundle\API\Mamba;
use Mamba\EncountersBundle\Helpers\Popularity;

/**
 * BillingController
 *
 * @package EncountersBundle
 */
class BillingController extends ApplicationController {

    const

        BILLING_ADD_ITEM_SQL = "
            INSERT INTO
                Encounters.Billing
            SET
                `user_id`          = :user_id,
                `operation_id`     = :operation_id,
                `amount`           = :amount,
                `amount_developer` = :amount_developer,
                `validation_id`    = :validation_id,
                `extra`            = :extra,
                `changed`          = FROM_UNIXTIME(:time),
                `billed`           = :billed
            ON DUPLICATE KEY UPDATE
                `changed` = FROM_UNIXTIME(:time),
                `billed`  = :billed
        "
    ;

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

//            'extra', // extra-params (необязательный параметр)

            'time', // unixts- время оплаты.
            'sig', // 6c5cb969eb69115daa029545ee23d4c5 - подпись данных. 
        ),

        /**
         * JSON Result
         *
         * @var array
         */
        $json = array(
            'status'  => 0,
            'message' => '',
            'data'    => array(

            ),
        )
    ;

    protected static

        /**
         * Рейты
         *
         * @var array
         */
        $rates = array(
            1  => 10,
            2  => 20,
            5  => 50,
            10 => 125,
            25 => 300,
        )
    ;

    /**
     * Index action
     *
     * @return Response
     */
    public function indexAction() {
        $Mamba = $this->getMamba();
        if (!$Mamba->getReady()) {
            return $this->redirect($this->generateUrl('welcome'));
        }

        if (!$this->getSearchPreferencesHelper()->get($webUserId = $this->getMamba()->getWebUserId())) {
            return $this->redirect($this->generateUrl('welcome'));
        }

        $dataArray = $this->getInitialData();
        return $this->TwigResponse("EncountersBundle:templates:billing.html.twig", $dataArray);
    }

    /**
     * Добавление заказа
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addServiceAction() {
        if ($webUserId = $this->getSession()->get(Mamba::SESSION_USER_ID_KEY)) {
            if ($service = $this->getRequest()->get('service')) {
                if (is_array($service) && count($service) <= 2 && isset($service['id']) && ($serviceId = (int) $service['id'])) {
                    $this->getServicesHelper()->add($webUserId, $service);
                } else {
                    list($this->json['status'], $this->json['message']) = array(1, "Invalid params");
                }
            } else {
                list($this->json['status'], $this->json['message']) = array(1, "Invalid params");
            }
        } else {
            list($this->json['status'], $this->json['message']) = array(1, "Invalid session");
        }

        return $this->JSONResponse($this->json);
    }

    /**
     * Gateway action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function gatewayAction() {
        $Request  = $this->getRequest();
        $postParams = $Request->request->all();

        if (count(array_intersect(array_keys($postParams), $this->requiredParams)) == count($this->requiredParams)) {
            $billingParams = array();
            foreach ($this->requiredParams as $requiredParam) {
                $billingParams[$requiredParam] = $postParams[$requiredParam];
            }

            if ($this->getMamba()->checkBillingSignature($postParams)) {
                list($appId, $webUserId, $operationId, $amount, $amountDeveloper, $validationId, $time, $billed) = array(
                    (int) $billingParams['app_id'],
                    (int) $billingParams['oid'],
                    (int) $billingParams['operation_id'],
                    (float) $billingParams['amount'],
                    (float) $billingParams['amount_developer'],
                    (int) $billingParams['validation_id'],
                    (int) $billingParams['time'],
                    false
                );

                $extra = '';
                if (isset($postParams['extra'])) {
                    $extra = $postParams['extra'];
                }

                $extraJSON = @json_decode($extra, true);
                if ($extraJSON && $extraJSON['service']['id'] == 1 && intval($amount) == 1) {
                    $this->getBatteryHelper()->set($webUserId, Battery::MAXIMUM_CHARGE);
                    $this->getNotificationsHelper()->add($webUserId, "Ура! Ваша батарейка полностью заряжена!");
                    $billed = true;
                } elseif (array_key_exists((int) $amount, self::$rates)) {
                    $this->getAccountHelper()->incr($webUserId, $incr = self::$rates[(int) $amount]);
                    $this->getNotificationsHelper()->add($webUserId, "Ура! Ваш счет пополнен на {$incr} сердечек!");
                    $billed = true;
                }

                $stmt = $this->getDoctrine()->getEntityManager()->getConnection()->prepare(self::BILLING_ADD_ITEM_SQL);

                $billed = intval($billed);
                $stmt->bindParam('user_id', $webUserId);
                $stmt->bindParam('operation_id', $operationId);
                $stmt->bindParam('amount', $amount);
                $stmt->bindParam('amount_developer', $amountDeveloper);
                $stmt->bindParam('validation_id', $validationId);
                $stmt->bindParam('extra', $extra);
                $stmt->bindParam('time', $time);
                $stmt->bindParam('billed', $billed);

                $result = $stmt->execute();
            }

            return new Response(/** null */);
        }
    }
}