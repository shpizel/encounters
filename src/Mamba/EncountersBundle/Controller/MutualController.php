<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Mamba\PlatformBundle\API\Mamba;
use PDO;

/**
 * MutualController
 *
 * @package EncountersBundle
 */
class MutualController extends ApplicationController {

    const

        /**
         * Запрос в базу на получение данных
         *
         * @var str
         */
        MUTUAL_SQL = "
            SELECT
                d.current_user_id
            FROM
                Encounters.Decisions d INNER JOIN Encounters.Decisions d2 on d.web_user_id = d2.current_user_id
            WHERE
                d.web_user_id = :web_user_id and
                d.current_user_id = d2.web_user_id and
                d.decision >=0 and
                d2.decision >= 0
            ORDER BY
              d.changed DESC
        "
    ;

    /**
     * Index action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page) {
        $Mamba = $this->getMamba();
        if (!$Mamba->getReady()) {
            return $this->redirect($this->generateUrl('welcome'));
        }

        if (!$this->getSearchPreferencesObject()->get($webUserId = $Mamba->get('oid'))) {
            return $this->redirect($this->generateUrl('welcome'));
        }

        $this->getCountersObject()->set($webUserId, 'mutual_unread', 0);

        $dataArray  = $this->getInitialData();

        /**
         * Пагинатор
         *
         * @author shpizel
         */
        $perPage = 25;
        $currentPage = (int) $this->getRequest()->query->get('page') ?: $page;
        $lastPage = ceil(intval($this->getCountersObject()->get($webUserId, 'mutual')) / $perPage);
        if ($currentPage > $lastPage) {
            $currentPage = $lastPage;
        }

        $dataArray['paginator'] = array(
            'current' => $currentPage,
            'last'    => $lastPage,
            'max'     => 12,
        );

        $data = array();
        $stmt = $this->getDoctrine()->getEntityManager()->getConnection()->prepare(self::MUTUAL_SQL . " LIMIT $perPage OFFSET " . (abs($dataArray['paginator']['current'] -1) * $perPage));
        $_webUserId = $webUserId;
        $stmt->bindParam('web_user_id',  $_webUserId);

        if ($result = $stmt->execute()) {
            $usersArray = array();
            while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $usersArray[(int) $item['current_user_id']] = 1;
            }

            $usersArray = array_reverse($usersArray, true);
            $usersArray = array_chunk($usersArray, 100, true);
            foreach ($usersArray as $key => $users) {
                $usersArray[$key] = array_reverse($users, true);
            }
            $usersArray = array_reverse($usersArray);

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
        }
        $dataArray['data'] = $data ?: null;

        $Response = $this->render("EncountersBundle:templates:mutual.html.twig", $dataArray);
        $Response->headers->set('P3P', 'CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
        return $Response;
    }
}