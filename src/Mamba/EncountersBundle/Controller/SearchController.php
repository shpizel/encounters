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

        if ($activeId = $this->getSession()->get('active_id')) {
            $initialData['active_id'] = $activeId;
            $this->getSession()->remove('active_id');
        }

        $Response = $this->render("EncountersBundle:templates:search.html.twig", $initialData);
        $Response->headers->set('P3P', 'CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
        return $Response;
    }

    private function getSearchPreferences($userId) {
        $Mamba = $this->getMamba();

        if ($result = $Mamba->Anketa()->getInfo($userId)) {
            $userInfo = array_shift($result);

            /**
             * Нас интересуют location, familiarity, interests
             *
             * @author shpizel
             */
            list($location, $familiarity, $interests) = [$userInfo['location'], $userInfo['familiarity'], $userInfo['interests']];

            if (preg_match("!или!iu", $familiarity['lookfor'], $result)) {
                $lookingFor = ['M', 'F'];
            } else if (preg_match("!парнем!iu", $familiarity['lookfor'], $result)) {
                $lookingFor = ['M'];
            } else if (preg_match("!девушкой!iu", $familiarity['lookfor'], $result)) {
                $lookingFor = ['F'];
            }

            if (preg_match("!возрасте\s(\d+)-(\d+)!", $familiarity['lookfor'], $result)) {
                $lookingFor[] = [$result[1], $result[2]];
            }

            var_dump($familiarity);
            var_dump($lookingFor);
        }
    }

    private function detector() {
        $Mamba = $this->getMamba();

        $contacts = $this->getMamba()->Contacts()->getContactList(100, false, [])['contacts'];
        usort($contacts, function($a, $b) {
                $a = $a['message_count'];
                $b = $b['message_count'];

                if ($a == $b) {
                    return 0;
                }
                return ($a < $b) ? -1 : 1;
        });

        foreach ($contacts as $k=>$contact) {
            if ($contact['info']['gender'] != 'F') {
                unset($contacts[$k]);
            }
        }

        $contacts = array_reverse($contacts);
        $max = $contacts[0]['message_count'];

        foreach ($contacts as $k=>$contact) {
            if ($max/($contact['message_count'] ?: 1) > 10) {
                unset($contacts[$k]);
            }
        }

        $ids = [];
        foreach ($contacts as $contact) {
            $ids[] = $contact['info']['oid'];
        }

        $info = $Mamba->Anketa()->getInfo($ids);

        foreach ($info as $anketa) {
            var_dump($anketa["info"]); echo "<br>";
        }
        exit();

        /**
         * Вообще говоря нужно отсеять из контактов контакты пола который я не ищу в своей анкете как бы..
         *
         *
         */

        var_dump($contacts);
    }
}