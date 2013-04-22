<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Core\MambaBundle\API\Mamba;
use PDO;

/**
 * VisitorsController
 *
 * @package EncountersBundle
 */
class VisitorsController extends ApplicationController {

    const

        /**
         * Запрос в базу на получение данных
         *
         * @var str
         */
        VISITORS_SQL = "
            SELECT
                *
            FROM
                Decisions
            WHERE
                current_user_id = :web_user_id
            ORDER BY
                changed DESC
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

        if (!$this->getSearchPreferencesHelper()->get($webUserId = $Mamba->get('oid'))) {
            return $this->redirect($this->generateUrl('welcome'));
        }

        $visitorsUnread = $this->getCountersHelper()->get($webUserId, 'visitors_unread');
        if ($page == 1) {
            $this->getCountersHelper()->set($webUserId, 'visitors_unread', 0);
        }

        $dataArray  = $this->getInitialData();

        $perPage = 25;
        $currentPage = (int) $this->getRequest()->query->get('page') ?: $page;
        $lastPage = ceil(intval($this->getCountersHelper()->get($webUserId, 'visitors')) / $perPage);
        if ($currentPage > $lastPage) {
            $currentPage = $lastPage;
        }

        $dataArray['paginator'] = array(
            'current' => $currentPage,
            'last'    => $lastPage,
            'max'     => 12,
        );

        $data = $json = array();
        $offset = $dataArray['paginator']['current'] > 0 ? ($dataArray['paginator']['current'] -1) * $perPage : 0;
        $stmt = $this->getDoctrine()->getEntityManager()->getConnection()->prepare(self::VISITORS_SQL . " LIMIT $perPage OFFSET {$offset}");
        $_webUserId = $webUserId;
        $stmt->bindParam('web_user_id',  $_webUserId);

        if ($result = $stmt->execute()) {
            $usersArray = array();
                while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $usersArray[(int) $item['web_user_id']] = (int) $item['decision'];
            }

            if ($usersArray) {
                $anketasArray = $Mamba->Anketa()->getInfo(array_keys($usersArray));

                foreach ($anketasArray as &$anketa) {

                    if (!isset($usersArray[$anketa['info']['oid']])) {
                        continue;
                    }

                    $json[$anketa['info']['oid']] = array(
                        'info' => array(
                            'id'               => $anketa['info']['oid'],
                            'name'             => $anketa['info']['name'],
                            'gender'           => $anketa['info']['gender'],
                            'age'              => $anketa['info']['age'],
                            'sign'             => $anketa['info']['sign'],
                            'small_photo_url'  => $anketa['info']['small_photo_url'],
                            'medium_photo_url' => $anketa['info']['medium_photo_url'],
                            'is_app_user'      => $anketa['info']['is_app_user'],
                            'location'         => $anketa['location'],
                            'flags'            => $anketa['flags'],
                            'familiarity'      => $anketa['familiarity'],
//                            'other'            => $anketa['other'],
                        ),
                    );

                    $anketa['decision'] = array($usersArray[$anketa['info']['oid']]);

                    if ($this->getPurchasedHelper()->exists($webUserId, $anketa['info']['oid'])) {
                        if ($tmp = $this->getViewedQueueHelper()->get($anketa['info']['oid'], $webUserId)) {
                            $anketa['decision'][] = $tmp['decision'];
                            $anketa['decision'][] = 0;

                            $visitorsUnread--;
                        } else {
                            $anketa['decision'][] = -2;
                            $anketa['decision'][] = (int) $visitorsUnread-- > 0;
                        }
                    } else {
                        $anketa['decision'][] = -2;
                        $anketa['decision'][] = (int) $visitorsUnread-- > 0;
                    }

                    $data[] = $anketa;
                }
            }

        }

        $dataArray['data'] = $data ?: null;
        $dataArray['json'] = json_encode($json) ?: null;

        return $this->TwigResponse("EncountersBundle:templates:visitors.html.twig", $dataArray);
    }
}