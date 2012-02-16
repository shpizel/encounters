<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Mamba\PlatformBundle\API\Mamba;

/**
 * VisitorsController
 *
 * @package EncountersBundle
 */
class VisitorsController extends ApplicationController {

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

        $visitors = $this->getDoctrine()
            ->getEntityManager()
            ->createQuery('SELECT d FROM EncountersBundle:Decisions d WHERE d.currentUserId = :webUserId ORDER BY d.changed ASC')
            ->setParameter('webUserId', $webUserId)
            ->getResult()
        ;

        $dataArray = $this->getInitialData();
        $dataArray['data'] = $visitors ?: null;

        return $this->render("EncountersBundle:templates:visitors.html.twig", $dataArray);
    }
}