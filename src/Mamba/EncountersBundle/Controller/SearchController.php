<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Mamba\PlatformBundle\API\Mamba;

/**
 * SearchController
 *
 * @package EncountersBundle
 */
class SearchController extends ApplicationController {

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

        $initialData = $this->getInitialData();
        $initialData['queues'] = array('current'=>null);
        if ($currentQueue = $this->getCurrentQueue()) {
            $initialData['queues']['current'] = json_encode($currentQueue);
        }

        $Response = $this->render("EncountersBundle:templates:search.html.twig", $initialData);
        $Response->headers->set('P3P', 'CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
        return $Response;
    }

    /**
     * Возвращает текущую очередь, для bind'а в страницу
     *
     * @return array|null
     */
    private function getCurrentQueue() {
        $Mamba = $this->getMamba();
        $webUserId = (int) $Mamba->get('oid');
        $json = array();

        if ($currentQueue = $this->getCurrentQueueObject()->getAll($webUserId)) {
            $currentQueue = array_reverse($currentQueue);
            $currentQueue = array_chunk($currentQueue, 10);
            foreach ($currentQueue as $key=>$subQueue) {
                $currentQueue[$key] = array_reverse($subQueue);
            }
            $currentQueue = array_reverse($currentQueue);

            $Mamba->multi();
            foreach ($currentQueue as $subQueue) {
                $Mamba->Anketa()->getInfo($subQueue);
            }
            $anketaInfoArray = $Mamba->exec();

            foreach ($anketaInfoArray as $chunk) {
                foreach ($chunk as $dataArray) {
                    $json[] = array(
                        'info' => array(
                            'id'               => $dataArray['info']['oid'],
                            'name'             => $dataArray['info']['name'],
                            'gender'           => $dataArray['info']['gender'],
                            'age'              => $dataArray['info']['age'],
                            'small_photo_url'  => $dataArray['info']['small_photo_url'],
                            'medium_photo_url' => $dataArray['info']['medium_photo_url'],
                            'is_app_user'      => $dataArray['info']['is_app_user'],
                        ),
                    );
                }
            }

            /**
             * Пересчет currentUser'ов
             *
             * @author shpizel
             */
            $currentUsers = array();
            foreach ($json as $userInfo) {
                if (isset($userInfo['info']['id'])) {
                    $currentUsers[] = $userInfo['info']['id'];
                }
            }

            if ($currentUsers) {
                $Mamba->multi();
                foreach ($currentUsers as $currentUserId) {
                    $Mamba->Photos()->get($currentUserId);
                }
                $dataArray = $Mamba->exec();

                foreach ($dataArray as $key=>$dataArray) {
                    if (isset($dataArray['photos'])) {
                        $json[$key]['photos'] = $dataArray['photos'];
                    }
                }

                foreach ($json as $key=>$dataArray) {
                    $currentUserId = $dataArray['info']['id'];
                    if (!isset($dataArray['photos'])) {
                        unset($json[$key]);

                        $this->getCurrentQueueObject()->remove($webUserId, $currentUserId);
                    }
                }
            }
        }

        return $json ?: null;
    }
}