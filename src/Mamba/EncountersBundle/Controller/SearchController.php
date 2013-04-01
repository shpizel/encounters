<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Core\MambaBundle\API\Mamba;

/**
 * SearchController
 *
 * @package EncountersBundle
 */
class SearchController extends ApplicationController {

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

        if (!$this->getSearchPreferencesObject()->get($webUserId = $Mamba->get('oid'))) {
            return $this->redirect($this->generateUrl('welcome'));
        }

        $initialData = $this->getInitialData();
        $initialData['queues'] = array('current' => null);

        if ($activeId = $this->getSession()->get('active_id')) {
            $initialData['active_id'] = $activeId;
            $this->getSession()->remove('active_id');
        }

        $Response = $this->render("EncountersBundle:templates:search.html.twig", $initialData);
        $Response->headers->set('P3P', 'CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
        return $Response;
    }
}