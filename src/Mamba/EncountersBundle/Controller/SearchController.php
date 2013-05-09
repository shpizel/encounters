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

        if (!$this->getSearchPreferencesHelper()->get($webUserId = $this->getMamba()->getWebUserId())) {
            return $this->redirect($this->generateUrl('welcome'));
        }

        $initialData = $this->getInitialData();
        $initialData['queues'] = array('current' => null);

        if ($activeId = $this->getSession()->get('active_id')) {
            $initialData['active_id'] = $activeId;
            $this->getSession()->remove('active_id');
        }

        return $this->TwigResponse("EncountersBundle:templates:search.html.twig", $initialData);
    }
}