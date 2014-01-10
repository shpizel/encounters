<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Core\MambaBundle\API\Mamba;
use PDO;

/**
 * MyChoiceController
 *
 * @package EncountersBundle
 */
class MyChoiceController extends ApplicationController {

    const

        /**
         * Запрос в базу на получение данных
         *
         * @var str
         */
        MYCHOICE_SQL = "
            SELECT
                *
            FROM
                Decisions
            WHERE
                web_user_id = :web_user_id AND
                decision >= 0
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

        if (!$this->getSearchPreferencesHelper()->get($webUserId = $Mamba->getWebUserId())) {
            return $this->redirect($this->generateUrl('welcome'));
        }

        $dataArray  = $this->getInitialData();

        $perPage = 25;
        $currentPage = (int) $this->getRequest()->query->get('page') ?: $page;
        $lastPage = ceil(intval($this->getCountersHelper()->get($webUserId, 'mychoice')) / $perPage);
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

        $startTime = microtime(true);
        $Query = $this->getMySQL()->getQuery($sql = self::MYCHOICE_SQL . " LIMIT $perPage OFFSET {$offset}");
        $_webUserId = $webUserId;
        $Query->bind('web_user_id',  $_webUserId);

        if ($result = $Query->execute()->getResult()) {
            $usersArray = array();
            while ($item = $Query->fetch(PDO::FETCH_ASSOC)) {
                $usersArray[(int) $item['current_user_id']] = (int) $item['decision'];
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
                        } else {
                            $anketa['decision'][] = -3;
                        }
                    } else {
                        $anketa['decision'][] = -2;
                    }

                    $data[] = $anketa;
                }
            } else {

            }
        }

        $dataArray['data'] = $data ?: null;
        $dataArray['json'] = json_encode($json) ?: null;

        $initialData['microtime'] = microtime(true);
        return $this->TwigResponse("EncountersBundle:templates:mychoice.html.twig", $dataArray);
    }
}