<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Mamba\EncountersBundle\Helpers\Gifts;
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
    public function indexAction() {
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
            /**
             * Если передан айдишник Мамбовский — нужно проверить есть ли контакт,
             * и если нет — создать его
             *
             * @author
             */
            if (!($Contact = $ContactsObject->getContact($webUserId, $currentUserId))) {
                $Contact = $ContactsObject->createContact($webUserId, $currentUserId);
            }
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
     * Messenger updater
     *
     * @return Response
     */
    public function getMessengerUpdateAction() {
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
                        if ($messages = $this->getMessagesObject()->getMessages($Contact, intval($this->getRequest()->request->get('last_message_id')))) {
                            foreach ($messages as $key=>$Message) {
                                $messages[$key] = $Message->toArray();
                                $messages[$key]['date'] = $this->getHumanDate($messages[$key]['timestamp']);

                                if ($Message->getType() == 'gift') {
                                    if ($giftData = json_decode($Message->getMessage(), true)) {
                                        $messages[$key]['gift'] = array(
                                            'comment' => $giftData['comment'],
                                            'url'     => \Mamba\EncountersBundle\Tools\Gifts\Gifts::getInstance()->getGiftById($giftData['gift_id'])->getUrl(),
                                        );
                                    }
                                }
                            }

                            $this->json['data']['messages'] = $messages;
                            $this->json['data']['unread_count'] = 0;

                            if ($reverseContact = $this->getContactsObject()->getContact($Contact->getRecieverId(), $Contact->getSenderId())) {
                                $this->json['data']['unread_count'] = $reverseContact->getUnreadCount();
                            }
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
        $Mamba = $this->getMamba();

        if (!$Mamba->getReady()) {
            list($this->json['status'], $this->json['message']) = array(1, "Mamba is not ready");
        } else {
            $webUserId = $Mamba->get('oid');

            $message   = $this->getRequest()->request->get('message');
            $contactId = (int) $this->getRequest()->request->get('contact_id');

            if ($message && strip_tags($message) && $contactId) {
                if (($Contact = $this->getContactsObject()->getContactById($contactId)) && $Contact->getSenderId() == $webUserId)  {
                    $Message = (new Message)
                        ->setContactId($Contact->getId())
                        ->setType('text')
                        ->setDirection('from')
                        ->setMessage($message)
                        ->setTimestamp(time())
                    ;

                    if ($Message = $this->getMessagesObject()->addMessage($Message)) {

                        /**
                         * Обновляем контакт
                         *
                         * @author shpizel
                         */
                        $Contact->setMessagesCount($Contact->getMessagesCount() + 1);
                        $Contact->setChanged(time());
                        $this->getContactsObject()->updateContact($Contact);

                        /**
                         * Сформируем основной вывод json
                         *
                         *
                         */
                        $this->json['data']['message'] = $Message->toArray();
                        $this->json['data']['message']['date'] = $this->getHumanDate($this->json['data']['message']['timestamp']);

                        /**
                         * Отправляем данные в другой контакт (обратный) и обновляем его
                         *
                         * @author shpizel
                         */
                        if (!($OppositeContact = $this->getContactsObject()->getContact($Contact->getRecieverId(), $Contact->getSenderId()))) {
                            $OppositeContact = $this->getContactsObject()->createContact($Contact->getRecieverId(), $Contact->getSenderId());
                        }

                        $Message->setContactId($OppositeContact->getId())->setDirection('to');
                        if ($this->getMessagesObject()->addMessage($Message)) {
                            $OppositeContact->setUnreadCount($OppositeContact->getUnreadCount() + 1);
                            $OppositeContact->setMessagesCount($OppositeContact->getMessagesCount() + 1);
                            $OppositeContact->setChanged(time());
                            $this->getContactsObject()->updateContact($OppositeContact);
                        }

                        $this->json['data']['unread_count'] = $OppositeContact->getUnreadCount();
                    } else {
                        list($this->json['status'], $this->json['message']) = array(4, "Could not send message");
                    }
                } else {
                    list($this->json['status'], $this->json['message']) = array(3, "Contact does not exists");
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
                    $contactData['rated'] = $this->getViewedQueueObject()->exists($this->webUserId, $Contact->getRecieverId());
                    $contacts[/*$Contact->getId()*/] = $contactData;
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

            $months = array(
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

            $date = str_replace(",", ' '. $months[date('n', $timestamp)] .",", $date);
        }

        return $date;
    }
}