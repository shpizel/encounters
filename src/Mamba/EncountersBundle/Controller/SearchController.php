<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Core\MambaBundle\API\Mamba;

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
        $initialData['queues'] = array('current' => null);
        /*if ($currentQueue = $this->getCurrentQueue()) {
            $initialData['queues']['current'] = json_encode($currentQueue);
        }*/

        /**
         * Если контакты пользователя сохранены — нужно отобразить их на странице в конфиг
         *
         * @author shpizel
         */
        if ($this->getRedis()->sCard($redisContactsKey = "contacts_by_{$webUserId}")) {
            $contacts = $this->getRedis()->sMembers($redisContactsKey);
            foreach ($contacts as $key => $userId) {
                $userId = (int) $userId;
                $contacts[$key] = $userId;

                if ($this->getVariablesObject()->get($userId, 'last_message_sent')) {
                    unset($contacts[$key]);
                }
            }

            $contacts = array_chunk($contacts, 100);
            $Mamba->multi();
            foreach ($contacts as $chunk) {
                $Mamba->Anketa()->getInfo($chunk, array());
            }

            if ($result = $Mamba->exec()) {
                $contacts = array();
                foreach ($result as $chunk) {
                    foreach ($chunk as $item) {
                        if ($item['info']['is_app_user'] == 0) {
                            $contacts[] = $item['info']['oid'];
                        }
                    }
                }

                if ($contacts) {
                    shuffle($contacts);
                    $contacts = array_chunk($contacts, 10);
                    $contacts = array_shift($contacts);

                    $initialData['non_app_users_contacts'] = $contacts;
                }
            }
        }

        $Response = $this->render("EncountersBundle:templates:search.html.twig", $initialData);
        $Response->headers->set('P3P', 'CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
        return $Response;
    }
}