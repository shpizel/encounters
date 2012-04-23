<?php
namespace Mamba\EncountersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Mamba\PlatformBundle\API\Mamba;
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

        if (!$this->getSearchPreferencesObject()->get($webUserId = $Mamba->get('oid'))) {
            return $this->redirect($this->generateUrl('welcome'));
        }

        $dataArray = $this->getInitialData();
        $Response = $this->render("EncountersBundle:templates:billing.html.twig", $dataArray);
        $Response->headers->set('P3P', 'CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
        return $Response;
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
                    $this->getServicesObject()->add($webUserId, $service);
                } else {
                    list($this->json['status'], $this->json['message']) = array(1, "Invalid params");
                }
            } else {
                list($this->json['status'], $this->json['message']) = array(1, "Invalid params");
            }
        } else {
            list($this->json['status'], $this->json['message']) = array(1, "Invalid session");
        }

        return
            new Response(json_encode($this->json), 200, array(
                    "content-type" => "application/json",
                )
            )
        ;
    }

    /**
     * Gateway action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function gatewayAction() {
        $Request  = $this->getRequest();
        $postParams = $Request->request->all();

        /** logger :) */
        file_put_contents("/tmp/extra.log", var_export($postParams, 1), FILE_APPEND);

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

                /** Костыль */
                $extra = isset($postParams['extra']) ? $postParams['extra'] : null;

                if ($service = $this->getServicesObject()->get($webUserId)) {
                    $serviceId = (int) $service['id'];
                    if ($serviceId == 1) {
                        $this->getBatteryObject()->set($webUserId, 5);

                        $this->getNotificationsObject()->add($webUserId, "Ура! Ваша батарейка заряжена на 100%!");
                    } elseif ($serviceId == 2) {
                        if (isset($service['user_id']) && ($currentUserId = (int) $service['user_id'])) {
                            $this->getPriorityQueueObject()->put($currentUserId, $webUserId);
                            $billed = true;

                            $this->getNotificationsObject()->add($webUserId, "Ура! Услуга успешно оплачена!");
                        }
                    } elseif ($serviceId == 3) {
                        $this->getEnergyObject()->set($webUserId, 600);
                        $billed = true;

                        $this->getNotificationsObject()->add($webUserId, "Ура! Теперь вы получите 100 внеочередных показов!");

                    } elseif ($serviceId == 4) {
                        $energy = $this->getEnergyObject()->get($webUserId);
                        $level = $this->getPopularityObject()->getLevel($energy);
                        if ($level < 16) {
                            $level = $level + 1;
                            $this->getEnergyObject()->set($webUserId, Popularity::$levels[$level]);
                            $billed = true;

                            $this->getNotificationsObject()->add($webUserId, "Ура! Вы перешли на новый уровень популярности!");
                        }
                    }
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