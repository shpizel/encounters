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

//        $visitors = $this->getDoctrine()
//            ->getEntityManager()
//            ->createQuery('SELECT d FROM EncountersBundle:Decisions d WHERE d.currentUserId = :webUserId ORDER BY d.changed ASC')
//            ->setParameter('webUserId', $webUserId)
//            ->getResult()
//        ;
//
//        $dataArray = $this->getInitialData();
//        $dataArray['data'] = $visitors ?: null;

        $dataArray  = $this->getInitialData();
//        $result = $this->getDoctrine()
//            ->getEntityManager()
//            ->createQuery('SELECT d FROM EncountersBundle:Decisions d WHERE d.webUserId = :webUserId and d.decision >= 0 ORDER BY d.changed ASC')
//            ->setParameter('webUserId', $webUserId)
//            ->getResult()
//        ;

        $data = array();
        $result = $this->getDoctrine()
            ->getEntityManager()
            ->createQuery('SELECT d FROM EncountersBundle:Decisions d WHERE d.currentUserId = :webUserId ORDER BY d.changed DESC')
            ->setParameter('webUserId', $webUserId)
            ->getResult()
        ;

        if ($result) {
            $usersArray = array();
            foreach ($result as $item) {
                $usersArray[$item->getWebUserId()] = $item->getDecision();
            }

            $usersArray = array_reverse($usersArray, true);
            $usersArray = array_chunk($usersArray, 100, true);
            foreach ($usersArray as $key => $users) {
                $usersArray[$key] = array_reverse($users, true);
            }
            $usersArray = array_reverse($usersArray, true);

            $Mamba->multi();
            foreach ($usersArray as $users) {
                $Mamba->Anketa()->getInfo(array_keys($users), array());
            }
            $anketasArray = $Mamba->exec();

            foreach ($anketasArray as $k => $anketasChunk) {
                foreach ($anketasChunk as &$anketa) {
                    $anketa['decision'] = array(
                        $usersArray[$k][$anketa['info']['oid']]
                    );

                    if ($this->getPurchasedObject()->exists($webUserId, $anketa['info']['oid'])) {
                        if ($tmp = $this->getViewedQueueObject()->get($anketa['info']['oid'], $webUserId)) {
                            $anketa['decision'][] = $tmp['decision'];
                        } else {
                            $anketa['decision'][] = -2;
                        }
                    } else {
                        $anketa['decision'][] = -2;
                    }
                }
                $data = array_merge($data, $anketasChunk);
            }
        }

        $dataArray['data'] = $data ?: null;

        $Response = $this->render("EncountersBundle:templates:visitors.html.twig", $dataArray);
        $Response->headers->set('P3P', 'CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
        return $Response;
    }
}