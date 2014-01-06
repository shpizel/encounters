<?php
namespace Mamba\EncountersBundle\Controller;

use Mamba\EncountersBundle\Controller\ApplicationController;
use Mamba\EncountersBundle\EncountersBundle;
use Mamba\EncountersBundle\Helpers\Gifts;
use Mamba\EncountersBundle\Helpers\Messenger\Message;
use Symfony\Component\HttpFoundation\Response;
use Core\MambaBundle\API\Mamba;
use Mamba\EncountersBundle\Helpers\Messenger\Contacts;
use PDO;

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

        if (!$this->getSearchPreferencesHelper()->get($this->webUserId = $webUserId = $this->getMamba()->getWebUserId())) {
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
            $this->webUserId = $webUserId = $this->getMamba()->getWebUserId();

            $limit = (int) $this->getRequest()->request->get('limit') ?: 20;
            $offset = (int) $this->getRequest()->request->get('offset');
            $requiredContactId = (int) $this->getRequest()->request->get('contact_id') ?: null;

            $this->json['data']['contacts'] = $this->getContacts($limit, $offset, $requiredContactId) ?: [];
            $this->json['data']['online'] = [];

            if ($this->getRequest()->request->get('online')) {
                 if ($onlineUsers = $this->getOnlineUsers()) {
                     if ($platformData = $Mamba->Anketa()->getInfo($onlineUsers, ['location'])) {
                         $platformData = array_filter($platformData, function($item) {
                             if (!$item['info']['square_photo_url']) {
                                 return false;
                             }

                             return true;
                         });

                         $platformData = array_values($platformData);

                         if ($platformData) {
                            $this->json['data']['online'] = $platformData;
                         }
                     }
                 }
            }
        }

        return $this->JSONResponse($this->json);
    }

    /**
     * Messenger contacts updater
     *
     * @return Response
     */
    public function getContactsUpdateAction() {
        $Mamba = $this->getMamba();

        if (!$Mamba->getReady()) {
            list($this->json['status'], $this->json['message']) = array(1, "Mamba is not ready");
        } else {
            $this->webUserId = $webUserId = $this->getMamba()->getWebUserId();
            $this->json['data']['contacts'] = $this->getContacts() ?: [];
        }

        return $this->JSONResponse($this->json);
    }

    private function getOnlineUsers() {
        if ($searchPreferences = $this->getSearchPreferencesHelper()->get($this->webUserId)) {
            $Connection = $this->getDoctrine()->getEntityManager()->getConnection();

            $stmt = $Connection
                ->prepare(
                    "select
                        la.user_id
                    from
                        Encounters.UserLastAccess la, Encounters.User u
                    where
                        u.user_id = la.user_id and
                        u.gender = :gender and
                        u.age >= :age_min and
                        u.age <= :age_max and
                        u.city_id = :city_id and
                        la.lastaccess > UNIX_TIMESTAMP(NOW()) - 15*3600
                    order by
                        la.lastaccess desc
                    limit 100"
                )
            ;

            $gender = $searchPreferences['gender'];
            $ageMin = $searchPreferences['age_from'];
            $ageMax = $searchPreferences['age_to'];
            $cityId = $searchPreferences['geo']['city_id'];

            $stmt->bindParam('gender', $gender, PDO::PARAM_STR);
            $stmt->bindParam('age_min', $ageMin, PDO::PARAM_STR);
            $stmt->bindParam('age_max', $ageMax, PDO::PARAM_STR);
            $stmt->bindParam('city_id', $cityId, PDO::PARAM_STR);

            $users = [];
            if ($result = $stmt->execute()) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $users[] = (int) $row['user_id'];
                }
            }

            if (count($users) < 100) {
                $stmt = $Connection
                    ->prepare(
                        "select
                            la.user_id
                        from
                            Encounters.UserLastAccess la, Encounters.User u
                        where
                            u.user_id = la.user_id and
                            u.gender = :gender and
                            u.age >= :age_min and
                            u.age <= :age_max and
                            la.lastaccess > UNIX_TIMESTAMP(NOW()) - 15*60
                        order by
                            la.lastaccess desc
                        limit 100"
                    )
                ;

                $stmt->bindParam('gender', $gender, PDO::PARAM_STR);
                $stmt->bindParam('age_min', $ageMin, PDO::PARAM_STR);
                $stmt->bindParam('age_max', $ageMax, PDO::PARAM_STR);

                if ($result = $stmt->execute()) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $users[] = (int) $row['user_id'];
                    }
                }
            }

            $users = array_unique($users);

            if (count($users) < 100) {
                $stmt = $Connection
                    ->prepare(
                        "select
                            la.user_id
                        from
                            Encounters.UserLastAccess la, Encounters.User u
                        where
                            u.user_id = la.user_id and
                            u.gender = :gender and
                            la.lastaccess > UNIX_TIMESTAMP(NOW()) - 15*60
                        order by
                            la.lastaccess desc
                        limit 100"
                    )
                ;

                $stmt->bindParam('gender', $gender, PDO::PARAM_STR);

                if ($result = $stmt->execute()) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $users[] = (int) $row['user_id'];
                    }
                }
            }

            $users = array_unique($users);

            if (count($users) < 100) {
                $stmt = $Connection
                    ->prepare(
                        "select
                            la.user_id
                        from
                            Encounters.UserLastAccess la, Encounters.User u
                        where
                            u.user_id = la.user_id and
                            u.gender = :gender
                        order by
                            la.lastaccess desc
                        limit 100"
                    )
                ;

                $stmt->bindParam('gender', $gender, PDO::PARAM_STR);
                if ($result = $stmt->execute()) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $users[] = (int) $row['user_id'];
                    }
                }
            }

            return array_slice($users, 0, 99);
        }
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
            $webUserId = $this->getMamba()->getWebUserId();

            $CountersHelper = $this->getCountersHelper();

            if ($contactId = (int) $this->getRequest()->request->get('contact_id')) {
                if (($Contact = $this->getContactsHelper()->getContactById($contactId)) && ($Contact->getSenderId() == $webUserId)) {

                    /** messages всегда должно быть в ответе, даже если [] */
                    $this->json['data']['messages'] = [];

                    if ($unreadCount = $Contact->getUnreadCount()) {
                        $this->getContactsHelper()->updateContact(
                            $Contact
                                ->setUnreadCount(0)
                        );

                        $levedbUnreadCount = $CountersHelper->get($webUserId, 'messages_unread');

                        if ($levedbUnreadCount >= $unreadCount) {
                            $CountersHelper->decr($webUserId, 'messages_unread', $unreadCount);
                        } else {
                            $CountersHelper->set($webUserId, 'messages_unread', 0);
                        }
                    }

                    $this->getGearman()->getClient()->doLowBackground(
                        EncountersBundle::GEARMAN_MESSENGER_UPDATE_COUNTERS_FUNCTION_NAME,
                        serialize(
                            array(
                                'user_id' => $webUserId,
                                'time'    => time(),
                            )
                        )
                    );

                    if ($lastMessageId = intval($this->getRequest()->request->get('first_message_id'))) {
                        $sort = 'ASC';
                    } elseif ($lastMessageId = intval($this->getRequest()->request->get('last_message_id'))) {
                        $sort = 'DESC';
                    } else {
                        $lastMessageId = 0;
                        $sort = "ASC";
                    }

                    if ($messages = $this->getMessagesHelper()->getMessages($Contact, $lastMessageId, $sort)) {
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
                    }

                    $this->json['data']['unread_count'] = 0;
                    $this->json['data']['dialog'] = $Contact->getInboxCount() && $Contact->getOutboxCount();

                    if ($reverseContact = $this->getContactsHelper()->getContact($Contact->getRecieverId(), $Contact->getSenderId())) {
                        $this->json['data']['unread_count'] = $reverseContact->getUnreadCount();
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

    /**
     * Send message action
     *
     * @return Response
     */
    public function sendMessageAction() {
        //sleep(1);
        $Mamba = $this->getMamba();

        $ContactsHelper = $this->getContactsHelper();
        $MessagesHelper = $this->getMessagesHelper();

        if (!$Mamba->getReady()) {
            list($this->json['status'], $this->json['message']) = array(1, "Mamba is not ready");
        } else {
            $webUserId = $this->getMamba()->getWebUserId();

            if (($message   = $this->cleanHTMLMessage($this->getRequest()->request->get('message'))) &&
                ($contactId = (int) $this->getRequest()->request->get('contact_id'))
            ) {
                if (($WebUserContact = $ContactsHelper->getContactById($contactId)) && ($WebUserContact->getSenderId() == $webUserId))  {
                    $CurrentUserContact = $this->getContactsHelper()->getContact($WebUserContact->getRecieverId(), $WebUserContact->getSenderId(), true);

                    /** messages всегда должно быть в ответе, даже если [] */
                    $this->json['data']['messages'] = [];

                    $Message = (new Message)
                        ->setContactId($WebUserContact->getId())
                        ->setType('text')
                        ->setDirection('outbox')
                        ->setMessage($message)
                        ->setTimestamp(time())
                    ;

                    if
                    (
                        /** или диалог или меньше 3х непрочитанных у обратного контакта */
                        (($WebUserContact->getInboxCount() && $WebUserContact->getOutboxCount()) || $CurrentUserContact->getUnreadCount() < 3) &&

                        /** пытаемся отправить сообщение */
                        ($Message = $MessagesHelper->addMessage($Message))
                    ) {

                        $this->getStatsHelper()->incr('messages-sent');

                        /** Обновляем контакт */
                        $this->getContactsHelper()->updateContact(
                            $WebUserContact
                                ->setOutboxCount($WebUserContact->getOutboxCount() + 1)
                                ->setChanged(time())
                        );

                        $messages = [$Message->toArray()];
                        $messages[0]['date'] = $this->getHumanDate($messages[0]['timestamp']);


                        if ($lastMessageId = (int) $this->getRequest()->request->get('last_message_id')) {

                            if ($unreadCount = $WebUserContact->getUnreadCount()) {
                                $this->getContactsHelper()->updateContact(
                                    $WebUserContact
                                        ->setUnreadCount(0)
                                );

                                $levedbUnreadCount = $CountersHelper->get($webUserId, 'messages_unread');

                                if ($levedbUnreadCount >= $unreadCount) {
                                    $CountersHelper->decr($webUserId, 'messages_unread', $unreadCount);
                                } else {
                                    $CountersHelper->set($webUserId, 'messages_unread', 0);
                                }
                            }

                            if ($messages = $MessagesHelper->getMessages($WebUserContact, $lastMessageId, 'DESC')) {
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

                        $this->getGearman()->getClient()->doLowBackground(
                            EncountersBundle::GEARMAN_MESSENGER_UPDATE_COUNTERS_FUNCTION_NAME,
                            serialize(
                                array(
                                    'user_id' => $webUserId,
                                    'time'    => time(),
                                )
                            )
                        );

                        $this->json['data']['messages'] = $messages;

                        $this->json['data']['unread_count'] = 0;
                        $this->json['data']['dialog'] = $WebUserContact->getInboxCount() && $WebUserContact->getOutboxCount();

                        /** Отправляем данные в другой контакт (обратный) и обновляем его */
                        if ($CurrentUserContact) {
                            if ($MessagesHelper->addMessage(
                                $Message
                                    ->setContactId($CurrentUserContact->getId())
                                    ->setDirection('inbox'))
                            ) {
                                $this->getStatsHelper()->incr('messages-sent');

                                $ContactsHelper->updateContact(
                                    $CurrentUserContact
                                        ->setUnreadCount($CurrentUserContact->getUnreadCount() + 1)
                                        ->setInboxCount($CurrentUserContact->getInboxCount() + 1)
                                        ->setChanged(time())
                                );

                                /** установим +1 к счетчику сообщений */
                                $this->getCountersHelper()->incr($WebUserContact->getRecieverId(), 'messages_unread');
                            }

                            $this->json['data']['unread_count'] = $CurrentUserContact->getUnreadCount();
                        }
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

        $AccountHelper = $this->getAccountHelper();
        $ContactsHelper = $this->getContactsHelper();
        $MessagesHelper = $this->getMessagesHelper();

        if (!$Mamba->getReady()) {
            list($this->json['status'], $this->json['message']) = array(1, "Mamba is not ready");
        } else {
            $webUserId = $this->getMamba()->getWebUserId();

            $currentUserId = (int) $this->getRequest()->request->get('current_user_id');
            $giftInfo = $this->getRequest()->request->get('gift');

            if ($currentUserId &&
                is_array($giftInfo) &&
                isset($giftInfo['id']) &&
                is_numeric($giftInfo['id']) &&
                ($Gift = \Mamba\EncountersBundle\Tools\Gifts\Gifts::getInstance()->getGiftById($giftId = (int) $giftInfo['id'])) &&
                isset($giftInfo['comment'])
            ) {
                $account = $AccountHelper->get($webUserId);

                if ($account >= ($cost = $Gift->getCost())) {
                    $account = $AccountHelper->decr($webUserId, $cost);
                    $this->getGiftsHelper()->add($webUserId, $currentUserId, $giftId, $comment = $giftInfo['comment']);

                    $this->getStatsHelper()->incr('gifts-sent');

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

                    /** установим +1 к счетчику анкеты */
                    $this->getCountersHelper()->incr($currentUserId, 'events_unread');

                    $WebUserContact = $ContactsHelper->getContact($webUserId, $currentUserId, true);
                    $CurrentUserContact = $ContactsHelper->getContact($currentUserId, $webUserId, true);

                    if ($WebUserContact) {

                        /** messages всегда должно быть в ответе, даже если [] */
                        $this->json['data']['messages'] = [];

                        $Message = (new Message)
                            ->setContactId($WebUserContact->getId())
                            ->setTimestamp(time())
                            ->setType('gift')
                            ->setDirection('outbox')
                            ->setMessage(array(
                                'gift_id' => $giftId,
                                'comment' => $comment,
                            ))
                        ;

                        if ($MessagesHelper->addMessage($Message)) {

                            /** Обновляем контакт */
                            $ContactsHelper->updateContact(
                                $WebUserContact
                                    ->setOutboxCount($WebUserContact->getOutboxCount() + 1)
                                    ->setChanged(time())
                            );

                            $messages = [$Message->toArray()];
                            $messages[0]['date'] = $this->getHumanDate($messages[0]['timestamp']);
                            if ($giftData = $Message->getMessage()) {
                                $messages[0]['gift'] = array(
                                    'comment' => $giftData['comment'],
                                    'url'     => \Mamba\EncountersBundle\Tools\Gifts\Gifts::getInstance()->getGiftById($giftData['gift_id'])->getUrl(),
                                );
                            }

                            if ($lastMessageId = (int) $this->getRequest()->request->get('last_message_id')) {

                                if ($unreadCount = $WebUserContact->getUnreadCount()) {
                                    $this->getContactsHelper()->updateContact(
                                        $WebUserContact
                                            ->setUnreadCount(0)
                                    );

                                    $levedbUnreadCount = $CountersHelper->get($webUserId, 'messages_unread');

                                    if ($levedbUnreadCount >= $unreadCount) {
                                        $CountersHelper->decr($webUserId, 'messages_unread', $unreadCount);
                                    } else {
                                        $CountersHelper->set($webUserId, 'messages_unread', 0);
                                    }
                                }

                                if ($messages = $MessagesHelper->getMessages($WebUserContact, $lastMessageId, 'DESC')) {
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

                            $this->getGearman()->getClient()->doLowBackground(
                                EncountersBundle::GEARMAN_MESSENGER_UPDATE_COUNTERS_FUNCTION_NAME,
                                serialize(
                                    array(
                                        'user_id' => $webUserId,
                                        'time'    => time(),
                                    )
                                )
                            );

                            $this->json['data']['messages'] = $messages;

                            $this->json['data']['unread_count'] = 0;
                            $this->json['data']['dialog'] = $WebUserContact->getInboxCount() && $WebUserContact->getOutboxCount();

                            if ($CurrentUserContact) {
                                if ($MessagesHelper->addMessage(
                                    $Message
                                        ->setDirection('inbox')
                                        ->setContactId($CurrentUserContact->getId())
                                )) {

                                    $this->getContactsHelper()->updateContact(
                                        $CurrentUserContact
                                            ->setUnreadCount($CurrentUserContact->getUnreadCount() + 1)
                                            ->setInboxCount($CurrentUserContact->getOutboxCount() + 1)
                                            ->setChanged(time())
                                    );

                                    /** установим +1 к счетчику сообщений */
                                    $this->getCountersHelper()->incr($currentUserId, 'messages_unread');
                                }

                                $this->json['data']['unread_count'] = $CurrentUserContact->getUnreadCount();
                            }
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

    /*public static*/ private function cleanHTMLMessage($message) {
        if (!strip_tags($message)) return;

        $message = preg_replace_callback("!</?(?P<tagname>\w+)(?P<attributes>[^>]*?)/?>!", function($data) {
            $tagname = strtolower($data['tagname']);
            $attributes = $data['attributes'];

            if ($tagname != 'img') {
                return (in_array($tagname, ['br'])) ? "<br>" : '';
            } else {
                $allowedAttrs = [];
                if (($attributes = trim($attributes)) && preg_match_all("!(?P<attrs>\w+)\s*?=\s*?[\"']+(?P<values>[^\"]+?)[\"']+!is", $attributes, $result)) {
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
                } else {
                    return "";
                }

                if (count($allowedAttrs) == 2) {
                    $tag = "<{$tagname}";
                    foreach ($allowedAttrs as $attr=>$val) {
                        $tag .= " {$attr}=\"{$val}\"";
                    }
                    $tag.=">";
                } else {
                    $tag = "";
                }

                return $tag;
            }
        }, $message);

        $message = str_ireplace(["<br>", "<br/>"], "\n", $message);
        $message = str_replace("&nbsp;", " ", $message);



        while (strpos($message, "  ") !== false) {
            $message = str_replace("  ", " ", $message);
        }

        $message = trim($message);
        $message = str_replace("\n", "<br>", $message);

        return $message;
    }
}