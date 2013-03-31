<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Mamba\EncountersBundle\Helpers\Messenger\Message;
use Symfony\Component\HttpFoundation\Response;
use Core\MambaBundle\API\Mamba;
use Mamba\EncountersBundle\Helpers\Messenger\Contacts;

/**
 * MessengerController
 *
 * @package EncountersBundle
 */
class MessengerController extends ApplicationController {

    protected

        /**
         * JSON Result
         *
         * @var array
         */
        $json = array(
            'status'  => 0,
            'message' => '',
            'data'    => [],
        )
    ;

    /**
     *
     *
     * @return Response
     */
    public function indexAction(){
        $Mamba = $this->getMamba();
        if (!$Mamba->getReady()) {
            return $this->redirect($this->generateUrl('welcome'));
        }

        if (!$this->getSearchPreferencesObject()->get($this->webUserId = $webUserId = $Mamba->get('oid'))) {
            return $this->redirect($this->generateUrl('welcome'));
        }

        $dataArray = $this->getInitialData();

        $ContactsObject = $this->getContactsObject();
        if ($currentUserId = (int) $this->getRequest()->query->get('id')) {

            if (!$ContactsObject->getContact($webUserId, $currentUserId)) {
                $ContactsObject->createContact($webUserId, $currentUserId);
            }

            if (!$ContactsObject->getContact($currentUserId, $webUserId)) {
                $ContactsObject->createContact($currentUserId, $webUserId);
            }
        }

        $dataArray['messages'] = [];
        if ($dataArray['contacts'] = $this->getContacts() ?: []) {
            $dataArray['messages'][current($dataArray['contacts'])['reciever_id']] = [];
        }

        return $this->render('EncountersBundle:templates:messenger.html.twig', $dataArray);
    }

    /**
     * Contacts getter action
     *
     * @return Response
     */
    public function getContactsAction() {
        $Mamba = $this->getMamba();

        if (!$Mamba->getReady()) {
            list($this->json['status'], $this->json['message']) = array(1, "Mamba is not ready");
        } else {
            $this->webUserId = $webUserId = $Mamba->get('oid');
            $this->json['data']['contacts'] = $this->getContacts() ?: [];
        }

        return
            new Response(json_encode($this->json), 200, array(
                    "content-type" => "application/json",
                )
            )
        ;
    }

    /**
     * Messages getter action
     *
     *
     */
    public function getMessagesAction() {
        $Mamba = $this->getMamba();

        if (!$Mamba->getReady()) {
            list($this->json['status'], $this->json['message']) = array(1, "Mamba is not ready");
        } else {
            $webUserId = $Mamba->get('oid');
            if ($contactId = (int) $this->getRequest()->request->get('contact_id')) {
                if ($Contact = $this->getContactsObject()->getContactById($contactId)) {
                    if ($Contact->getSenderId() == $webUserId) {
                        if ($messages = $this->getMessagesObject()->getMessages($Contact)) {
                            foreach ($messages as $key=>$Message) {
                                $messages[$key] = $Message->toArray();
                                $messages[$key]['date'] = $this->getHumanDate($messages[$key]['timestamp']);
                            }
                            $this->json['data'] = $messages;
                        }
                    } else {
                        list($this->json['status'], $this->json['message']) = array(3, "Invalid contact");
                    }
                } else {
                    list($this->json['status'], $this->json['message']) = array(2, "Contact does not exists");
                }
            } else {
                list($this->json['status'], $this->json['message']) = array(2, "Invalid params");
            }
        }

        return
            new Response(json_encode($this->json), 200, array(
                    "content-type" => "application/json",
                )
            )
        ;
    }

    /**
     * Contacts getter action
     *
     * @return Response
     */
    public function sendMessageAction() {
        $message   = $this->getRequest()->request->get('message');
        $contactId = (int) $this->getRequest()->request->get('contact_id');

        if ($message && strip_tags($message) && $contactId) {
            if ($Contact = $this->getContactsObject()->getContactById($contactId)) {
                $Message = (new Message)
                    ->setContactId($Contact->getId())
                    ->setType('text')
                    ->setDirection(mt_rand(0, 1) ? 'from': 'to')
                    ->setMessage($message)
                    ->setTimestamp(time())
                ;

                if ($Message = $this->getMessagesObject()->addMessage($Message)) {
                    $this->json['data'] = $Message->toArray();
                    $this->json['data']['date'] = $this->getHumanDate($this->json['data']['timestamp']);

                    /**
                     * Нужно отправить данные в другой контакт через очередь
                     *
                     * @author shpizel
                     */
                    if ($_Contact = $this->getContactsObject()->getContact($Contact->getRecieverId(), $Contact->getSenderId())) {
                        $this->getMessagesObject()->addMessage($Message->setContactId($_Contact->getId()));
                    }
                } else {
                    list($this->json['status'], $this->json['message']) = array(3, "Could not send message");
                }
            } else {
                list($this->json['status'], $this->json['message']) = array(2, "Contact does not exists");
            }
        } else {
            list($this->json['status'], $this->json['message']) = array(1, "Invalid params");
        }

        return
            new Response(json_encode($this->json), 200, array(
                    "content-type" => "application/json",
                )
            )
        ;
    }

    /**
     * Contacts getter with platform data
     *
     * @return array
     */
    private function getContacts() {
        if ($Contacts = $this->getContactsObject()->getContacts($this->webUserId)) {
            $userIds = array_map(function($item){return $item->getRecieverId();}, $Contacts);
            $userIds[] = $this->webUserId;

            $apiData = $this->getMamba()->Anketa()->getInfo($userIds);
            $profilesData = [];
            foreach ($apiData as $userData) {
                $profilesData[$userData['info']['oid']] = $userData;
                try {
                    $userPhotos = $this->getMamba()->Photos()->get($userData['info']['oid']);
                    $profilesData[$userData['info']['oid']]['info']['photos_count'] = count($userPhotos['photos']);
                } catch (\Exception $e) {

                }
            }

            unset($apiData);

            $contacts = array();
            foreach ($Contacts as $Contact) {
                $contactData = $Contact->toArray();
                if (isset($profilesData[$Contact->getRecieverId()])) {
                    $contactData['platform'] = $profilesData[$Contact->getRecieverId()];
                    $contacts[$Contact->getId()] = $contactData;
                }
            }

            return $contacts;
        }
    }

    private function getHumanDate($timestamp) {
        if (date('dmY') == date('dmY', $timestamp)) {
            $date = date("H:i", $timestamp);
        } else {
            $date = date("d, H:i", $timestamp);

            $monthes = array(
                1 => 'января',
                2 => 'февраля',
                3 => 'марта',
                4 => 'апреля',
                5 => 'мая',
                6 => 'июня',
                7 => 'июля',
                8 => 'августа',
                9 => 'сентября',
                10 => 'октября',
                11 => 'ноября',
                12 => 'декабря',
            );

            $date = str_replace(",", $monthes[date('n', $timestamp)] .",", $date);
        }

        return $date;
    }
}