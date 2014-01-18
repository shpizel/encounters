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
                decisions.*
            FROM
                Decisions decisions
            LEFT JOIN
                UserExists `exists`
            ON
                `exists`.user_id = decisions.web_user_id
            WHERE
                decisions.current_user_id = :web_user_id AND
                `exists`.`exists` = 1
            ORDER BY
                decisions.changed DESC"
    ;

    /**
     * Index action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page) {
        $startTime = microtime(true);

        $Mamba = $this->getMamba();
        if (!$Mamba->getReady()) {
            return $this->redirect($this->generateUrl('welcome'));
        }

        if (!$this->getSearchPreferencesHelper()->get($webUserId = $this->getMamba()->getWebUserId())) {
            return $this->redirect($this->generateUrl('welcome'));
        }

        $visitorsUnread = $this->getCountersHelper()->get($webUserId, 'visitors_unread');
        if ($page == 1) {
            $this->getCountersHelper()->set($webUserId, 'visitors_unread', 0);
        }

        $dataArray  = [];

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
        $Query = $this->getMySQL()->getQuery($sql = self::VISITORS_SQL . " LIMIT $perPage OFFSET {$offset}");
        $_webUserId = $webUserId;
        $Query->bind('web_user_id',  $_webUserId);

        if ($result = $Query->execute()->getResult()) {
            $usersArray = array();
                while ($item = $Query->fetch(PDO::FETCH_ASSOC)) {
                $usersArray[(int) $item['web_user_id']] = (int) $item['decision'];
            }

            if ($usersArray) {
                $anketasArray = $this->getUsersHelper()->getInfo(array_keys($usersArray));

                foreach ($anketasArray as &$anketa) {

                    if (!isset($usersArray[$anketa['info']['user_id']])) {
                        continue;
                    }

                    $json[$anketa['info']['user_id']] = array(
                        'info' => array(
                            'id'               => $anketa['info']['user_id'],
                            'name'             => $anketa['info']['name'],
                            'gender'           => $anketa['info']['gender'],
                            'age'              => $anketa['info']['age'],
                            'sign'             => $anketa['info']['sign'],
                            'small_photo_url'  => $anketa['avatar']['small_photo_url'],
                            'medium_photo_url' => $anketa['avatar']['medium_photo_url'],
                            'is_app_user'      => $anketa['info']['is_app_user'],
                            'location'         => $anketa['location'],
                            'flags'            => $anketa['flags'],
                            'familiarity'      => $anketa['familiarity'],
//                            'other'            => $anketa['other'],
                        ),
                    );

                    $anketa['decision'] = array($usersArray[$anketa['info']['user_id']]);

                    if ($this->getPurchasedHelper()->exists($webUserId, $anketa['info']['user_id'])) {
                        if ($tmp = $this->getViewedQueueHelper()->get($anketa['info']['user_id'], $webUserId)) {
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

        $dataArray = array_merge($dataArray, $this->getInitialData());

        $dataArray['data'] = $data ?: null;
        if (!$data) {
            $this->getCountersHelper()->set($webUserId, 'visitors', 0);
        } elseif ($page == 1 && count($data) < $perPage) {
            $this->getCountersHelper()->set($webUserId, 'visitors', count($data));
        }

        $dataArray['json'] = json_encode($json) ?: null;

        return $this->TwigResponse("EncountersBundle:templates:visitors.html.twig", $dataArray);
    }
}