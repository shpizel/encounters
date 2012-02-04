<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Mamba\PlatformBundle\API\Mamba;

/**
 * WelcomeController
 *
 * @package EncountersBundle
 */
class WelcomeController extends ApplicationController {

    /**
     * Index action
     *
     * @return \Symfony\Bundle\FrameworkBundle\Controller\RedirectResponse|\Symfony\Bundle\FrameworkBundle\Controller\Response
     */
    public function indexAction() {
        $Request  = $this->getRequest();
        $Session  = $this->getSession();
        $Mamba    = $this->getMamba();
        $Redis    = $this->getRedis();

        $PlatformSettings = $this->getPlatformSettingsObject();

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
            if ($redisPlatformParams = $PlatformSettings->get($mambaUserId)) {
                if ($getPlatformParams && ($redisPlatformParams['oid'] == $getPlatformParams['oid'])) {

                    /**
                     * Пришли какие-то платформенные ГЕТ-параметры, неизвестно фрейм обновился или внешний фрейм
                     *
                     * @author shpizel
                     */
                    if ($getPlatformParams['sid'] != $redisPlatformParams['sid']) {
                        $PlatformSettings->set($getPlatformParams);
                    } elseif ($referer = $Request->server->get('HTTP_REFERER')) {
                        if ($params = @parse_url($referer, PHP_URL_QUERY)) {
                            @parse_str($params, $params);
                            if (is_array($params) && isset($params['app_id'])) {
                                if ($params['app_id'] = $Request->query->get('app_id')) {
                                    $PlatformSettings->set($getPlatformParams);
                                }
                            }
                        }
                    }
                }
            } elseif ($getPlatformParams) {
                $PlatformSettings->set($getPlatformParams);
            } else {
                $Response = $this->render('EncountersBundle:Welcome:sorry.html.twig');
                $Response->headers->set('Content-Type', 'text/plain');
                return $Response;
            }
        } else {
            $Response = $this->render('EncountersBundle:Welcome:sorry.html.twig');
            $Response->headers->set('Content-Type', 'text/plain');
            return $Response;
        }

        /**
         * Если нет предустановленных параметров поиска — кидаем на настройки
         *
         * @author shpizel
         */
        if (!$this->getPreferencesObject()->get($mambaUserId)) {
            return $this->redirect($this->generateUrl('preferences'));
        }

        /** В общем случае кидаем на игру */
        return $this->redirect($this->generateUrl('game'));
    }
}