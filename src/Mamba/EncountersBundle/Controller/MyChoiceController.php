<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Mamba\PlatformBundle\API\Mamba;

/**
 * MyChoiceController
 *
 * @package EncountersBundle
 */
class MyChoiceController extends ApplicationController {

    /**
     * Index action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {
        $Mamba = $this->get('Mamba');
        if (!$Mamba->getReady()) {
            return $this->redirect($this->generateUrl('welcome'));
        }

        if (!$this->getSearchPreferencesObject()->get($webUserId = $Mamba->get('oid'))) {
            return $this->redirect($this->generateUrl('welcome'));
        }

        $myChoice = $this->getDoctrine()
            ->getEntityManager()
                ->createQuery('SELECT d FROM EncountersBundle:Decisions d WHERE d.webUserId = :webUserId ORDER BY d.changed ASC')
                    ->setParameter('webUserId', $webUserId)
                ->getResult()
        ;

        $dataArray = $this->getInitialData();
        $dataArray['data'] = $myChoice ?: null;

        return $this->render("EncountersBundle:templates:mychoice.html.twig", $dataArray);
    }
}