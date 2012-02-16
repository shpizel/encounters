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

        $rsm = new ResultSetMapping;
        $rsm->addScalarResult('current_user_id', 'current_user_id');

        $query = $this->getDoctrine()->getEntityManager()->createNativeQuery(self::MUTUAL_SQL, $rsm);
        $query->setParameter(1, $webUserId);
        $mutualData = $query->getResult();

        $dataArray = $this->getInitialData();
        $dataArray['data'] = $mutualData ?: null;

        return $this->render("EncountersBundle:templates:mutual.html.twig", $dataArray);
    }
}