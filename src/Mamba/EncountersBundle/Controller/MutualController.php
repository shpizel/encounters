<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Mamba\PlatformBundle\API\Mamba;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * MutualController
 *
 * @package EncountersBundle
 */
class MutualController extends ApplicationController {

    const

        MUTUAL_SQL = "
            SELECT
              d.current_user_id
            FROM
              Encounters.Decisions d INNER JOIN Encounters.Decisions d2 on d.web_user_id = d2.current_user_id
            WHERE
              d.web_user_id = ? and
              d.current_user_id = d2.web_user_id and
              d.decision >=0 and
              d2.decision >= 0
        "
    ;

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

//        $rsm = new ResultSetMapping;
//        $rsm->addScalarResult('current_user_id', 'current_user_id');
//        $query = $this->getDoctrine()->getEntityManager()->createNativeQuery(self::MUTUAL_SQL, $rsm);
//        $query->setParameter(1, $webUserId);
//        $mutualData = $query->getResult();
//
//        $dataArray = $this->getInitialData();
//        $dataArray['data'] = $mutualData ?: null;

        $dataArray  = $this->getInitialData();
        $result = $this->getDoctrine()
            ->getEntityManager()
            ->createQuery('SELECT d FROM EncountersBundle:Decisions d WHERE d.webUserId = :webUserId and d.decision >= 0 ORDER BY d.changed ASC')
            ->setParameter('webUserId', $webUserId)
            ->getResult()
        ;

        if ($result) {

            $usersArray = array();
            foreach ($result as $item) {
                $usersArray[$item->getCurrentUserId()] = $item->getDecision();
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

            $data = array();
            foreach ($anketasArray as $k => $anketasChunk) {
                foreach ($anketasChunk as &$anketa) {
                    $anketa['decision'] = array(
                        $usersArray[$k][$anketa['info']['oid']]
                    );

                    if ($this->getPurchasedObject()->exists($webUserId, $anketa['info']['oid'])) {
                        if ($tmp = $this->getViewedQueueObject()->get($webUserId, $anketa['info']['oid'])) {
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

            $dataArray['data'] = $data ?: null;
        }

        $Response = $this->render("EncountersBundle:templates:mutual.html.twig", $dataArray);
        $Response->headers->set('P3P', 'CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
        return $Response;
    }
}