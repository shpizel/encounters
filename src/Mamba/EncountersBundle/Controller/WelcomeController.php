<?php
namespace Mamba\EncountersBundle\Controller;
use Symfony\Component\HttpFoundation\Response;

use Mamba\EncountersBundle\Controller\ApplicationController;

use Core\MambaBundle\API\Mamba;
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
//        return $this->render('EncountersBundle:templates:500.html.twig', array('routes' => json_encode($this->getRoutes())));

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

            if (!session_id()) {
                session_id(md5($webUserId . "-" . time() . "-" . microtime()));
            }

            $Session->start();
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
            if (isset($getParams['extra'])) {
                $extra = $getParams['extra'];
                if (preg_match("!profile(\d+)$!", $extra, $data)) {
                    $profileId = array_pop($data);

                    /** Кидаем на анкету внутри выбиратора */
                    $Response = $this->redirect($this->generateUrl('profile') . "?id={$profileId}");
                    $Response->headers->set('P3P', 'CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');

                    return $Response;
                } elseif (preg_match("!meet(\d+)$!", $extra, $data)) {
                    if (!$this->getViewedQueueObject()->exists($webUserId, $currentUserId = (int) array_pop($data))) {
                        if ($webUserId != $currentUserId && !$this->getCurrentQueueObject()->exists($webUserId, $currentUserId)) {
                            $this->getCurrentQueueObject()->put($webUserId, $currentUserId);
                        }
                    }

                    $Session->set('active_id', intval($getParams['extra']));
                } elseif (in_array($extra, ['search', 'mychoice', 'visitors', 'mutual'])) {
                    $Response = $this->redirect($this->generateUrl($extra));
                    $Response->headers->set('P3P', 'CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');

                    return $Response;
                }
            }

            return $this->render('EncountersBundle:templates:login.html.twig');
        } elseif ($this->getRequest()->getMethod() == 'POST') {
            if (!$this->getSearchPreferencesObject()->get($webUserId)) {
                $Response = $this->redirect($this->generateUrl('preferences'));
                $Response->headers->set('P3P', 'CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
                return $Response;
            }

            /** В общем случае кидаем на поиск */
            $Response = $this->redirect($this->generateUrl('search'));
            $Response->headers->set('P3P', 'CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');

            return $Response;
        }
    }
}