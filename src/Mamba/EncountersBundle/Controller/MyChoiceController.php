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

        if (!$this->getSearchPreferencesObject()->get($webUserId = $Mamba->getWebUserId())) {
            return $this->redirect($this->generateUrl('welcome'));
        }

        $dataArray  = $this->getInitialData();

        /**
         * Пагинатор
         *
         * @author shpizel
         */
        $perPage = 25;
        $currentPage = (int) $this->getRequest()->query->get('page') ?: $page;
        $lastPage = ceil(intval($this->getCountersObject()->get($webUserId, 'mychoice')) / $perPage);
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
        $stmt = $this->getDoctrine()->getEntityManager()->getConnection()->prepare(self::MYCHOICE_SQL . " LIMIT $perPage OFFSET {$offset}");
        $_webUserId = $webUserId;
        $stmt->bindParam('web_user_id',  $_webUserId);

        if ($result = $stmt->execute()) {
            $usersArray = array();
            while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $usersArray[(int) $item['current_user_id']] = (int) $item['decision'];
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

                    if ($this->getPurchasedObject()->exists($webUserId, $anketa['info']['oid'])) {
                        if ($tmp = $this->getViewedQueueObject()->get($anketa['info']['oid'], $webUserId)) {
                            $anketa['decision'][] = $tmp['decision'];
                        } else {
                            $anketa['decision'][] = -3;
                        }
                    } else {
                        $anketa['decision'][] = -2;
                    }

                    $data[] = $anketa;
                }
            }
        }

        $dataArray['data'] = $data ?: null;
        $dataArray['json'] = json_encode($json) ?: null;

        $Response = $this->render("EncountersBundle:templates:mychoice.html.twig", $dataArray);
        $Response->headers->set('P3P', 'CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
        return $Response;
    }
}