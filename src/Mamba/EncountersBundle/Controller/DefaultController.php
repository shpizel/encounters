<?php
namespace Mamba\EncountersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Mamba\PlatformBundle\API\Mamba;

/**
 * DefaultController
 *
 * @package EncountersBundle
 */
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
                if ($getPlatformParams && $redisPlatformParams['oid'] == $getPlatformParams['oid']) {
                    /**
                     * Пришли какие-то платформенные ГЕТ-параметры, неизвестно фрейм обновился или внешний фрейм
                     *
                     * @author shpizel
                     */
                    if ($getPlatformParams['sid'] != $redisPlatformParams['sid']) {
                        $this->storePlatformParams($getPlatformParams);
                    } elseif ($referer = $Request->server->get('HTTP_REFERER')) {
                        if ($params = @parse_url($referer, PHP_URL_QUERY)) {
                            @parse_str($params, $params);
                            if (is_array($params) && isset($params['app_id'])) {
                                if ($params['app_id'] = $Request->query->get('app_id')) {
                                    $this->storePlatformParams($getPlatformParams);
                                }
                            }
                        }
                    }
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

        exit(var_dump($redisPlatformParams));
        var_dump($this->get('mamba')->Anketa()->getInfo(array(724727670)));
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

        $platformParams['last_query_time'] = time();

        foreach ($platformParams as $key=>$value) {
            $this->get('redis')->hSet(
                sprintf(Mamba::REDIS_HASH_USER_PLATFORM_PARAMS_KEY, $mambaUserId),
                $key,
                $value
            );
        }
    }
}