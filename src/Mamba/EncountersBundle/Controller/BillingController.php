<?php
namespace Mamba\EncountersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Mamba\PlatformBundle\API\Mamba;
use Mamba\EncountersBundle\Entity\Billing;

/**
 * BillingController
 *
 * @package EncountersBundle
 */
class BillingController extends ApplicationController {

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
        if (count(array_intersect(array_keys($postParams), $this->requiredParams)) == count($this->requiredParams)) {
            $billingParams = array();
            foreach ($this->requiredParams as $requiredParam) {
                $billingParams[$requiredParam] = $postParams[$requiredParam];
            }

            if ($this->getMamba()->checkBillingSignature($postParams)) {
                $item = new Billing();

                $item->setAppId((int) $billingParams['app_id']);
                $item->setUserId($webUserId = (int) $billingParams['oid']);
                $item->setOperationId((int) $billingParams['operation_id']);
                $item->setAmount((float) $billingParams['amount']);
                $item->setAmountDeveloper((float) $billingParams['amount_developer']);
                $item->setValidationId((int) $billingParams['validation_id']);
                $item->setTime((int) $billingParams['time']);

                if ($service = $this->getServicesObject()->get($webUserId)) {
                    $serviceId = (int) $service['id'];
                    if ($serviceId == 1) {
                        $this->getBatteryObject()->set($webUserId, 5);
                        $item->setBilled(true);

                        $this->getNotificationsObject()->add($webUserId, "Ура! Ваша батарейка заряжена на 100%!");
                    } elseif ($serviceId == 2) {
                        if (isset($service['user_id']) && ($currentUserId = (int) $service['user_id'])) {
                            $this->getPriorityQueueObject()->put($currentUserId, $webUserId);
                            $item->setBilled(true);

                            $this->getNotificationsObject()->add($webUserId, "Ура! Услуга успешно оплачена!");
                        } else {
                            $item->setBilled(false);
                        }
                    } elseif ($serviceId == 3) {
                        $this->getEnergyObject()->incr($webUserId, 15*100);
                        $item->setBilled(true);

                        $this->getNotificationsObject()->add($webUserId, "Ура! Теперь вы получите 100 внеочередных показов!");

                    }
                } else {
                    $item->setBilled(false);
                }

                $em = $this->getDoctrine()->getEntityManager();
                $em->persist($item);
                $em->flush();
            }

            return new Response(/** null */);
        }
    }
}