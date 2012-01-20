<?php

namespace Mamba\EncountersBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Mamba\PlatformBundle\API\Mamba;

class DefaultController extends Controller {

    public function indexAction() {
        list($Session, $Memcache, $Mamba, $GET) = array(
            $this->get('session'),
            $this->get('memcache'),
            $this->get('mamba'),
            $this->getRequest()->query->all(),
        );

        $sessionVars = array_keys($Session->all());
        if (count(array_intersect(Mamba::$mambaRequiredGetParams, $sessionVars)) == count(Mamba::$mambaRequiredGetParams)) {
            /**
             * Необходимые Мамба GET-параметры есть в сессионных переменных и Мамба сама их использует
             *
             * @author shpizel
             */
        } elseif (count(array_intersect(array_keys($GET), Mamba::$mambaRequiredGetParams)) == count(Mamba::$mambaRequiredGetParams)) {
            /**
             * Необходимые Мамба GET-параметры есть в запросе, получим их, проверим и запишем в сессию
             *
             * @author shpizel
             */
            $params = array();
            foreach (Mamba::$mambaRequiredGetParams as $param) {
                $params[$param] = $GET[$param];
            }

            if ($Mamba->checkAuthKey($params)) {
                $Mamba->setOptions($params);
                $Mamba->setReady(true);
                foreach ($params as $key=>$value) {
                    $Session->set($key, $value);
                }
            } else {
                return new Response("Internal error..");
            }
        } else {
            return new Response("Internal error..");
        }

        /**
         * Инициализация пройдена — можно писать рабочий код
         *
         * @author shpizel
         */
        header("Content-type: text/html; charset=utf8;");
        var_dump($Mamba->Anketa()->getInfo(array($this->getRequest()->query->get('oid'))));
            exit();
    }
}