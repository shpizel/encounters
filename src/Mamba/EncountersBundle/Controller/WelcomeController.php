<?php
namespace Mamba\EncountersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Mamba\PlatformBundle\API\Mamba;
use Mamba\EncountersBundle\EncountersBundle;

/**
 * WelcomeController
 *
 * @package EncountersBundle
 */
class WelcomeController extends Controller {

    /**
     * Index action
     *
     * @return \Symfony\Bundle\FrameworkBundle\Controller\RedirectResponse|\Symfony\Bundle\FrameworkBundle\Controller\Response
     */
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

        if (isset($getParams['extra'])) {
            $getParams['extra'] = urlencode($getParams['extra']);
        }

        if (count(array_intersect(array_keys($getParams), Mamba::$mambaRequiredGetParams)) == count(Mamba::$mambaRequiredGetParams)) {
            foreach (Mamba::$mambaRequiredGetParams as $param) {
                $getPlatformParams[$param] = $getParams[$param];
            }

            if (!$Mamba->checkAuthKey($getParams)) {
                $getPlatformParams = array();
            }
        }

        /**
         * Попробуем узнать айдишник Мамба-юзера и попробуем взять его пользовательские настройки платформы из Redis
         *
         * @author shpizel
         */
        $mambaUserId = null;
        if ($userSessionExists = $Session->has(Mamba::SESSION_USER_ID_KEY)) {
            $mambaUserId = $Session->get(Mamba::SESSION_USER_ID_KEY);
        } elseif ($getPlatformParams) {
            $mambaUserId = (int) $getPlatformParams['oid'];
            $this->get('session')->set(Mamba::SESSION_USER_ID_KEY, $mambaUserId);
        }

        if ($mambaUserId) {
            if ($redisPlatformParams = $Redis->hGetAll(sprintf(Mamba::REDIS_HASH_USER_PLATFORM_PARAMS_KEY, $mambaUserId))) {
                if ($getPlatformParams && ($redisPlatformParams['oid'] == $getPlatformParams['oid'])) {
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
        } else {
            $Response = $this->render('EncountersBundle:Default:sorry.html.twig');
            $Response->headers->set('Content-Type', 'text/plain');
            return $Response;
        }

        exit();
        /**
         * Если нет предустановленных параметров поиска — кидаем на настройки
         *
         * @author shpizel
         */
        if (!$Redis->hLen(sprintf(EncountersBundle::REDIS_HASH_USER_SEARCH_PREFERENCES_KEY, $Session->get(Mamba::SESSION_USER_ID_KEY)))) {
            return $this->redirect($this->generateUrl('preferences'));
        }

        /** В общем случае кидаем на игру */
        return $this->redirect($this->generateUrl('game'));
    }

    /**
     * (пере)Записать параметры платформы в Redis
     *
     * @param $platformParams
     */
    protected function storePlatformParams($platformParams) {
        if (isset($platformParams['auth_key'])) {
            unset($platformParams['auth_key']);
        }

        $platformParams['last_query_time'] = time();

        foreach ($platformParams as $key=>$value) {
            $this->get('redis')->hSet(
                sprintf(Mamba::REDIS_HASH_USER_PLATFORM_PARAMS_KEY, (int) $platformParams['oid']),
                $key,
                $value
            );
        }
    }
}