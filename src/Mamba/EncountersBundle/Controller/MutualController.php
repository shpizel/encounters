<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Core\MambaBundle\API\Mamba;
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
                d2.web_user_id as `current_user_id`
            FROM
                Encounters.Decisions d INNER JOIN Encounters.Decisions d2 on d.web_user_id = d2.current_user_id
            WHERE
                d.web_user_id = :web_user_id and
                d.current_user_id = d2.web_user_id and
                d.decision >=0 and
                d2.decision >= 0
            ORDER BY
                d2.changed DESC
        "
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

        if ($page == 1) {
            $this->getCountersHelper()->set($webUserId, 'mutual_unread', 0);
        }

        $dataArray = [];

        /**
         * Пагинатор
         *
         * @author shpizel
         */
        $perPage = 25;

        $currentPage = (int) $this->getRequest()->query->get('page') ?: $page;
        $lastPage = ceil(intval($this->getCountersHelper()->get($webUserId, 'mutual')) / $perPage);
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
        $Query = $this->getMySQL()->getQuery($sql = self::MUTUAL_SQL . " LIMIT $perPage OFFSET {$offset}");
        $_webUserId = $webUserId;
        $Query->bind('web_user_id',  $_webUserId);

        if ($result = $Query->execute()->getResult()) {
            $usersArray = array();
            while ($item = $Query->fetch(PDO::FETCH_ASSOC)) {
                $usersArray[(int) $item['current_user_id']] = 1;
            }

            if ($usersArray) {
                $anketasArray = $this->getUsersHelper()->getInfo(array_keys($usersArray));

                $data = array();
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
                        if ($tmp = $this->getViewedQueueHelper()->get($webUserId, $anketa['info']['user_id'])) {
                            $anketa['decision'][] = $tmp['decision'];
                        } else {
                            $anketa['decision'][] = -2;
                        }
                    } else {
                        $anketa['decision'][] = -2;
                    }

                    $data[] = $anketa;
                }
            }
        }

        $dataArray = array_merge($this->getInitialData(), $dataArray);

        $dataArray['data'] = $data ?: null;
        if (!$data) {
            $this->getCountersHelper()->set($webUserId, 'mutual', 0);
        } elseif ($page == 1 && count($data) < $perPage) {
            $this->getCountersHelper()->set($webUserId, 'mutual', count($data));
        }

        $dataArray['json'] = json_encode($json) ?: null;

        $Response = $this->TwigResponse("EncountersBundle:templates:mutual.html.twig", $dataArray);

        return $Response;
    }
}