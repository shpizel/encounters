<?php
namespace Mamba\EncountersBundle\Controller;
use Symfony\Component\HttpFoundation\Response;

use Mamba\EncountersBundle\Controller\ApplicationController;

use Mamba\PlatformBundle\API\Mamba;
use Mamba\EncountersBundle\EncountersBundle;

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
        /** Debug 500 page */
        #return $this->render('EncountersBundle:templates:500.html.twig', array('routes' => json_encode($this->getRoutes())));

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

            $Session->start();

            /** Встроим защиту от непустой сессии: если сессия НЕпустая — разрушаем, логируем, рестартуем до тех пор пока не добьемся уникальности */
            if ($Session->all() && $Session->get(Mamba::SESSION_USER_ID_KEY) != $webUserId) {

                $log = date("Y-m-d H:i:s") . PHP_EOL;
                $log.= "session_id:" . PHP_EOL;
                $log.= $Session->getId() . PHP_EOL;
                $log.= "getPlatformParams:" . PHP_EOL;
                $log.= print_r($getPlatformParams, true) . PHP_EOL;
                $log.= "Session data:" . PHP_EOL;
                $log.= print_r($Session->all(), true);
                $log.= str_repeat("=", 16) . PHP_EOL;

                file_put_contents("/tmp/session.log", $log, FILE_APPEND);

                $Session->migrate();
                $Session->clear();
            }

            $Session->set(Mamba::SESSION_USER_ID_KEY, $webUserId);

            $lastAccessTime = $this->getVariablesObject()->get($webUserId, 'lastaccess');
            if (time() - $lastAccessTime > 8*3600) {
                $this->getGearman()->getClient()->doLowBackground(EncountersBundle::GEARMAN_ACHIEVEMENT_SET_FUNCTION_NAME, serialize(array(
                    'webUserId'     => $webUserId,
                    'currentUserId' => null,
                    'decision'      => null,
                    'time'          => time(),
                )));

                foreach (range(-1, 1) as $decision) {
                    $this->getCountersObject()->set($webUserId, "noretry-($decision)", 0);
                }
            }

            $this->getVariablesObject()->set($webUserId, 'lastaccess', time());
        } elseif ($Session->has(Mamba::SESSION_USER_ID_KEY)) {
            $webUserId = $Session->get(Mamba::SESSION_USER_ID_KEY);
            $this->getVariablesObject()->set($webUserId, 'lastaccess', time());
        } else {
            return $this->render('EncountersBundle:templates:500.html.twig', array('routes' => json_encode($this->getRoutes())));
        }

        if ($this->getRequest()->getMethod() == 'GET') {
            return $this->render('EncountersBundle:templates:login.html.twig');
        } elseif ($this->getRequest()->getMethod() == 'POST') {
            if (!$this->getSearchPreferencesObject()->get($webUserId)) {
                $Response = $this->redirect($this->generateUrl('preferences'));
                $Response->headers->set('P3P', 'CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
                return $Response;
            }

            /*if (isset($getParams['extra']) && ($extra = $getParams['extra'])) {

            }*/

            /** В общем случае кидаем на поиск */
            $Response = $this->redirect($this->generateUrl('search'));
            $Response->headers->set('P3P', 'CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
            return $Response;
        }
    }
}