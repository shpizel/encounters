<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Core\MambaBundle\API\Mamba;

/**
 * ProfileController
 *
 * @package EncountersBundle
 */
class ProfileController extends ApplicationController {

    /**
     * Index action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {
        $Mamba = $this->getMamba();
        if (!$Mamba->getReady()) {
            return $this->redirect($this->generateUrl('welcome'));
        }

        if (!$this->getSearchPreferencesObject()->get($webUserId = $Mamba->getWebUserId())) {
            return $this->redirect($this->generateUrl('welcome'));
        }

        $dataArray  = $this->getInitialData();

        /** Определяем user_id */

        if (!($currentUserId = (int) $this->getRequest()->query->get('id'))) {
            $currentUserId = (int) $webUserId;
        }

        if (!($profile = $Mamba->nocache()->Anketa()->getInfo($currentUserId))) {
            return $this->redirect($this->generateUrl('welcome'));
        }

        $dataArray['profile'] = $profile[0];
        if (!($dataArray['profile']['myself'] = $currentUserId == $webUserId)) {
            $dataArray['profile']['rated'] = $this->getViewedQueueObject()->exists($webUserId, $currentUserId);
        }
        $dataArray['profile']['photos'] = $Mamba->Photos()->get($currentUserId)['photos'];

        /** перемешаем интересы */
        if (isset($dataArray['profile']['interests'])) {
            shuffle($dataArray['profile']['interests']);
        }

        $dataArray['profile']['gifts'] = array();

        $Response = $this->render("EncountersBundle:templates:profile.html.twig", $dataArray);
        $Response->headers->set('P3P', 'CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
        return $Response;
    }
}