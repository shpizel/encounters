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

        $getPlatformParams = array();
        $getParams = $Request->query->all();

        if (isset($getParams['extra'])) {
            $getParams['extra'] = urlencode($getParams['extra']);
        }

        if (count(array_intersect(array_keys($getParams), Mamba::$mambaRequiredGetParams)) == count(Mamba::$mambaRequiredGetParams)) {
            foreach (Mamba::$mambaRequiredGetParams as $param) {
                $getPlatformParams[$param] = $getParams[$param];
            }

            if ($Mamba->checkAuthKey($getParams)) {
                $this->getPlatformSettingsObject()->set($getPlatformParams);
            } else {
                $getPlatformParams = array();
            }
        }

        if ($getPlatformParams) {
            $webUserId = (int) $getPlatformParams['oid'];
            $this->get('session')->set(Mamba::SESSION_USER_ID_KEY, $webUserId);
        } elseif ($Session->has(Mamba::SESSION_USER_ID_KEY)) {
            $webUserId = $Session->get(Mamba::SESSION_USER_ID_KEY);
        } else {
            return $this->render('EncountersBundle:templates:500.html.twig', array(
                    'routes' => json_encode($this->getRoutes()))
            );
        }

        if (!$this->getSearchPreferencesObject()->get($webUserId)) {
            $Response = $this->redirect($this->generateUrl('preferences'));
            $Response->headers->set('P3P', 'CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
            return $Response;
        }

        /**
         * @todo: Нужно сделать правильную работу с extra-параметрами
         * Когда у нас будет какая-то ошибка в голосовании, мы сделаем топ-редирект на страницу с extra
         * И приложение сразу перейдет на нужную страницу
         *
         * @author shpizel
         */

        /** В общем случае кидаем на поиск */
        $Response = $this->redirect($this->generateUrl('search'));
        $Response->headers->set('P3P', 'CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
        return $Response;
    }
}