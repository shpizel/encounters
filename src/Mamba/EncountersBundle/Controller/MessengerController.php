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

        if (!$this->getSearchPreferencesHelper()->get($this->webUserId = $webUserId = $Mamba->get('oid'))) {
            return $this->redirect($this->generateUrl('welcome'));
        }

        $dataArray = $this->getInitialData();

        if ($currentUserId = (int) $this->getRequest()->query->get('id')) {
            if ($currentUserId != $webUserId) {
                $Contact = $this->getContactsHelper()->getContact($webUserId, $currentUserId, true);
                $dataArray['contact_id'] = $Contact->getId();
            }
        }

        return $this->TwigResponse('EncountersBundle:templates:messenger.html.twig', $dataArray);
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

            $limit = (int) $this->getRequest()->request->get('limit') ?: 20;
            $offset = (int) $this->getRequest()->request->get('offset');
            $requiredContactId = (int) $this->getRequest()->request->get('contact_id') ?: null;


            $this->json['data']['contacts'] = $this->getContacts($limit, $offset, $requiredContactId) ?: [];
        }

        return $this->JSONResponse($this->json);
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

        return $this->JSONResponse($this->json);
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
                if ($Contact = $this->getContactsHelper()->getContactById($contactId)) {
                    if ($Contact->getSenderId() == $webUserId) {

                        /** messages всегда должно быть в ответе, даже если [] */
                        $this->json['data']['messages'] = [];

                        if ($Contact->getUnreadCount()) {
                            $Contact->setUnreadCount(0);
                            $this->getContactsHelper()->updateContact($Contact);
                        }

                        if ($messages = $this->getMessagesHelper()->getMessages($Contact, intval($this->getRequest()->request->get('last_message_id')))) {
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
                            $this->json['data']['dialog'] = $Contact->getInboxCount() && $Contact->getOutboxCount();

                            if ($reverseContact = $this->getContactsHelper()->getContact($Contact->getRecieverId(), $Contact->getSenderId())) {
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

        return $this->JSONResponse($this->json);
    }

    public function cleanHTMLMessage($message) {
        if (!strip_tags($message)) return;

        $message = preg_replace_callback("!<(?P<tagname>\w+)(?P<attributes>[^>]*?)>!", function($data) {
            $tagname = $data['tagname'];
            $attributes = $data['attributes'];

            if ($tagname != 'img') {
                return "<{$tagname}>";
            } else {
                $allowedAttrs = [];
                if (($attributes = trim($attributes)) && preg_match_all("!(?P<attrs>\w+)\s*?=\s*?\"+(?P<values>[^\"]+?)\"+!is", $attributes, $result)) {
                    $attrs = $result['attrs'];
                    $values = $result['values'];

                    foreach ($attrs as $attrIndex=>$attr) {
                        $value = $values[$attrIndex];

                        if ((strtolower($attr) == 'class') && preg_match("!^smile s-\d+$!", $value)) {
                            $allowedAttrs[$attr] = $value;
                        } elseif (strtolower($attr) == 'src' && $value == '/bundles/encounters/images/pixel.gif') {
                            $allowedAttrs[$attr] = $value;
                        }
                    }
                }

                $tag = "<{$tagname}";
                foreach ($allowedAttrs as $attr=>$val) {
                    $tag .= " {$attr}=\"{$val}\"";
                }
                $tag.=">";

                return $tag;
            }
        }, $message);

        return $message;
    }

    /**
     * Send message action
     *
     * @return Response
     */
    public function sendMessageAction() {
        $Mamba = $this->getMamba();

        if (!$Mamba->getReady()) {
            list($this->json['status'], $this->json['message']) = array(1, "Mamba is not ready");
        } else {
            $webUserId = $Mamba->get('oid');

            $message   = $this->cleanHTMLMessage($this->getRequest()->request->get('message'));
            $contactId = (int) $this->getRequest()->request->get('contact_id');

            if ($message && $contactId) {
                if (($WebUserContact = $this->getContactsHelper()->getContactById($contactId)) && $WebUserContact->getSenderId() == $webUserId)  {
                    $Message = (new Message)
                        ->setContactId($WebUserContact->getId())
                        ->setType('text')
                        ->setDirection('outbox')
                        ->setMessage($message)
                        ->setTimestamp(time())
                    ;

                    if ($Message = $this->getMessagesHelper()->addMessage($Message)) {

                        /** Обновляем контакт */
                        $this->getContactsHelper()->updateContact(
                            $WebUserContact
                                ->setOutboxCount($WebUserContact->getOutboxCount() + 1)
                                ->setChanged(time())
                        );

                        /** Сформируем основной вывод json */
                        $this->json['data']['message'] = $Message->toArray();
                        $this->json['data']['message']['date'] = $this->getHumanDate($this->json['data']['message']['timestamp']);

                        /** Отправляем данные в другой контакт (обратный) и обновляем его */
                        if (!($CurrentUserContact = $this->getContactsHelper()->getContact($WebUserContact->getRecieverId(), $WebUserContact->getSenderId()))) {
                            $CurrentUserContact = $this->getContactsHelper()->createContact($WebUserContact->getRecieverId(), $WebUserContact->getSenderId());
                        }

                        $Message->setContactId($CurrentUserContact->getId())->setDirection('inbox');
                        if ($this->getMessagesHelper()->addMessage($Message)) {
                            $this->getContactsHelper()->updateContact(
                                $CurrentUserContact
                                    ->setUnreadCount($CurrentUserContact->getUnreadCount() + 1)
                                    ->setInboxCount($CurrentUserContact->getOutboxCount() + 1)
                                    ->setChanged(time())
                            );
                        }

                        $this->json['data']['unread_count'] = $CurrentUserContact->getUnreadCount();
                        $this->json['data']['dialog'] = $WebUserContact->getInboxCount() && $WebUserContact->getOutboxCount();
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

        return $this->JSONResponse($this->json);
    }

    /**
     * Send gift action
     *
     * @return Response
     */
    public function sendGiftAction() {
        $Mamba = $this->getMamba();

        $ContactsObject = $this->getContactsHelper();
        $MessagesObject = $this->getMessagesHelper();

        if (!$Mamba->getReady()) {
            list($this->json['status'], $this->json['message']) = array(1, "Mamba is not ready");
        } else {
            $webUserId = $Mamba->get('oid');
            $currentUserId = (int) $this->getRequest()->request->get('user_id');

            $giftInfo = $this->getRequest()->request->get('gift');

            if ($currentUserId &&
                is_array($giftInfo) &&
                isset($giftInfo['id']) &&
                is_numeric($giftInfo['id']) &&
                ($Gift = \Mamba\EncountersBundle\Tools\Gifts\Gifts::getInstance()->getGiftById($giftId = (int) $giftInfo['id'])) &&
                isset($giftInfo['comment'])
            ) {
                $Account = $this->getAccountHelper();
                $account = $Account->get($webUserId);

                if ($account >= ($cost = $Gift->getCost())) {
                    $account = $Account->decr($webUserId, $cost);
                    $this->getGiftsHelper()->add($webUserId, $currentUserId, $giftId, $comment = $giftInfo['comment']);

                    $userInfo = $this->getMamba()->Anketa()->getInfo($webUserId);

                    $this->json['data'] = array(
                        'account' => $account,
                        'gift'    => array(
                            'url' => $Gift->getUrl(),
                            'comment' => $comment,
                            'sender' => array(
                                'user_id' => $userInfo[0]['info']['oid'],
                                'name' => $userInfo[0]['info']['name'],
                                'age' => $userInfo[0]['info']['age'],
                                'city' => $userInfo[0]['location']['city'],
                            ),
                        ),
                    );

                    if ($Contact = $ContactsObject->getContact($webUserId, $currentUserId, true)) {
                        $Message = (new Message)
                            ->setContactId($Contact->getId())
                            ->setTimestamp(time())
                            ->setType('gift')
                            ->setDirection('outbox')
                            ->setMessage(array(
                                'gift_id' => $giftId,
                                'comment' => $comment,
                            ))
                        ;

                        if ($MessagesObject->addMessage($Message)) {


                            $messages = [$Message->toArray()];
                            if ($lastMessageId = (int) $this->getRequest()->request->get('last_message_id')) {
                                if ($messages = $MessagesObject->getMessages($Contact, $lastMessageId, 'DESC')) {
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
                                }
                            }

                            $this->json['data']['messages'] = $messages;

                            $this->json['data']['unread_count'] = 0;
                            $this->json['data']['dialog'] = $Contact->getInboxCount() && $Contact->getOutboxCount();

                            $Contact = $ContactsObject->getContact($currentUserId, $webUserId, true);
                            $MessagesObject->addMessage($Message->setDirection('inbox')->setContactId($Contact->getId()));

                            $this->json['data']['unread_count'] = $Contact->getUnreadCount();
                        }
                    }

                } else {
                    list($this->json['status'], $this->json['message']) = array(3, "Account is not enough for charge battery");
                }
            } else {
                list($this->json['status'], $this->json['message']) = array(2, "Invalid params");
            }
        }

        return $this->JSONResponse($this->json);
    }

    /**
     * Contacts getter with platform data
     *
     * @param int $limit
     * @param int $offset
     * @param null $requiredContactId
     * @return array
     */
    private function getContacts($limit = 20, $offset = 0, $requiredContactId = null) {
        if ($Contacts = $this->getContactsHelper()->getContacts($this->webUserId, $limit, $offset)) {
            if (is_int($requiredContactId) && ($Contact = $this->getContactsHelper()->getContactById($requiredContactId))) {
                $exists = false;
                foreach ($Contacts as $item) {
                    if ($item->getId() == $requiredContactId) {
                        $exists = true;
                        break;
                    }
                }

                if (!$exists) {
                    $Contacts[] = $Contact;
                }
            }

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

            $lastaccessData = $this->getVariablesHelper()->getMulti($userIds, ['lastaccess']);

            $contacts = array();
            foreach ($Contacts as $Contact) {
                $contactData = $Contact->toArray();
                if (isset($profilesData[$Contact->getRecieverId()])) {
                    $contactData['platform'] = $profilesData[$Contact->getRecieverId()];
                    $contactData['rated'] = $this->getViewedQueueHelper()->exists($this->webUserId, $Contact->getRecieverId());

                    $lastaccess = (int) $lastaccessData[$Contact->getRecieverId()]['lastaccess'];
                    $contactData['online'] = ($lastaccess && (time() - $lastaccess < 15*60));
                    $contactData['lastaccess'] = $lastaccess ? $this->getHumanDate($lastaccess) : null;

                    $contacts[] = $contactData;
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