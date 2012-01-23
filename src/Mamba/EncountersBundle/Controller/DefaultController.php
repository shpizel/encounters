<?php

namespace Mamba\EncountersBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Mamba\PlatformBundle\API\Mamba;

class DefaultController extends Controller {

    public function indexAction() {
        $Request  = $this->getRequest();
        $Session  = $this->get('session');
        $Mamba    = $this->get('mamba');
        $Memcache = $this->get('memcache');
        $Redis    = $this->get('redis');
        $Gearman  = $this->get('gearman');

        /**
         * Проверим новые поступления параметров
         *
         * @author shpizel
         */
        $getPlatformParams = array();
        $getParams = $Request->query->all();
        if (count(array_intersect(array_keys($getParams), Mamba::$mambaRequiredGetParams)) == count(Mamba::$mambaRequiredGetParams)) {
            foreach (Mamba::$mambaRequiredGetParams as $param) {
                $getPlatformParams[$param] = $getParams[$param];
            }

            if (!$Mamba->checkAuthKey($getPlatformParams)) {
                $getPlatformParams = array();
            }
        }

        /**
         * Проверим, существует ли пользовательская сессия, и если пользовательская сессия существует
         * попробуем взять из Redis текущие пользовательские настройки платформы
         *
         * @author shpizel
         */
        if ($userSessionExists = $Session->has(Mamba::SESSION_USER_ID_KEY)) {
            $mambaUserId = $Session->get(Mamba::SESSION_USER_ID_KEY);
            if ($redisPlatformParams = $Redis->hGetAll(sprintf(Mamba::REDIS_HASH_USER_PLATFORM_PARAMS_KEY, $mambaUserId))) {
                if ($getPlatformParams &&
                    $redisPlatformParams['sid'] != $getPlatformParams['sid'] &&
                    $redisPlatformParams['oid'] == $getPlatformParams['oid'])
                {
                    $this->storePlatformParams($getPlatformParams);
                }
            } else {
                $this->storePlatformParams($getPlatformParams);
            }
        } elseif ($getPlatformParams) {
            $this->storePlatformParams($getPlatformParams);
        } else {
            $Response = $this->render('EncountersBundle:Default:sorry.html.twig');
            $Response->headers->set('Content-Type', 'text/plain');
            return $Response;
        }

//        exit(var_dump($this->get('mamba')->Anketa()->getInfo(array(560015854))));
//        return new Response($x, 200, array('Content-type'=> 'text/plain'));
        return $this->render('EncountersBundle:Default:index.html.twig');
    }

    /**
     * Сохранить данные и запустить обновления
     *
     * @param $platformParams
     */
    protected function storePlatformParams($platformParams) {
        $this->get('session')->set(
            Mamba::SESSION_USER_ID_KEY,
            $mambaUserId = (int) $platformParams['oid']
        );

        if (isset($platformParams['auth_key'])) {
            unset($platformParams['auth_key']);
        }

        foreach ($platformParams as $key=>$value) {
            $this->get('redis')->hSet(
                sprintf(Mamba::REDIS_HASH_USER_PLATFORM_PARAMS_KEY, $mambaUserId),
                $key,
                $value
            );
        }
    }
}