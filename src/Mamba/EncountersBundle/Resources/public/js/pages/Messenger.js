/**
 * Messenger
 *
 * @author shpizel
 */
$Messenger = {

    locks: {},

    /**
     * Инициализация интерфейса
     *
     * @init
     */
    initUI: function() {
        $(document).click(function(){
            var $orangeMenu = $(".orange-menu .drop_down");
            if ($orangeMenu.is(':visible')) {
                $orangeMenu.hide();
                $("div.layout-content").removeClass('show-present');
                $(".orange-menu .item.gift").removeClass('item-current');
            }
        });

        if ($Config.get('debug')) {
            window.setTimeout(function() {
                $("div.sf-toolbarreset").parent().hide();
            }, 500);
        }

        $Messenger.$userInfo.initUI();
        $Messenger.$userInfo.$gifts.initUI();
        $Messenger.$contactList.initUI();
        $Messenger.$messages.initUI();
        $Messenger.$sendForm.initUI();
    },

    acquireLock: function($key) {
        if (!$key) {
            $key = 'default';
        }

        if ($Messenger.locks.hasOwnProperty($key)) {
            if ($Messenger.locks[$key] == false) {
                return $Messenger.locks[$key] = true;
            }
        } else {
            return $Messenger.locks[$key] = true;
        }

        return false;
    },

    freeLock: function($key) {
        if (!$key) {
            $key = 'default';
        }

        $Messenger.locks[$key] = false;
    },

    initUpdateTimer: function() {
        window.setInterval(
            function() {
                if (!$Messenger.acquireLock()) return;

                $Tools.ajaxPost('messenger.update', {}, function($data) {
                    if ($data.status == 0 && $data.message == '') {
                        var $contacts = $data.data.contacts, $contactsObject = {};
                        for (var $i=0;$i<$contacts.length;$i++) {
                            $contactsObject[$contacts[$i].contact_id] = $contacts[$i];
                        }

                        var $contacts = $Config.get('contacts'), $item;
                        for (var $contactId in $contactsObject) {
                            $contacts[$contactId] = $contactsObject[$contactId];
                        }

                        $Config.set('contacts', $contactsObject = $contacts);

                        var $sortArray = [];
                        for (var $contactId in $contactsObject) {
                            $sortArray.push([$contactsObject[$contactId]['changed'], $contactsObject[$contactId]]);
                        }

                        $sortArray.sort(function($a, $b) {
                            return - $a[0] + $b[0];
                        });

                        if ($sortArray.length > 0) {
                            $Messenger.$contactList.setNotEmpty();

                            for (var $i=0;$i<$sortArray.length;$i++) {
                                $item = $sortArray[$i][1];

                                if ($Messenger.$contactList.exists($item['contact_id'])) {
                                    $Messenger.$contactList.updateContact($item);
                                } else {
                                    $Messenger.$contactList.addContact($item);
                                }

                            }
                        } else {
                            $Messenger.$contactList.setEmpty();
                        }

                        /**
                         * Работа с сообщениями
                         *
                         * @author shpizel
                         */
                    }

                    $Messenger.freeLock();
                }, function() {
                    $Messenger.freeLock();
                });
            },
            $Messenger.updateTimeout
        );
    },

    updateTimeout: 5000,

    /**
     * Запуск страницы
     *
     * @run page
     */
    run: function() {

    },


    /**
     * Показываем лоадер на всю страницу
     *
     * @shows page loader
     */
    showLoader: function() {
        $("div.app_message-layout").addClass("loader-big");
    },

    /**
     * Скрываем лоадер на всю страницу
     *
     * @hides page loader
     */
    hideLoader: function() {
        $("div.app_message-layout").removeClass("loader-big");
    },

    /**
     * Контакт лист
     *
     * @object
     */
    $contactList: {

        showLoader: function() {
            $("div.b-list_users div.b-list_loading").css('visibility', 'visible');
        },

        hideLoader: function() {
            $("div.b-list_users div.b-list_loading").css('visibility', 'hidden');
        },

        /**
         * Init UI
         *
         * @init UI
         */
        initUI: function() {
            var $contactId = $Config.get('contact_id'), $postData = {};
            if ($contactId) {
                $postData = {'contact_id': $contactId};
            }

            $Tools.ajaxPost('messenger.contacts.get', $postData, function($data) {
                if ($data.status == 0 && $data.message == '') {
                    var $contactsArray = $data.data.contacts, $contactsObject = {};
                    for (var $i=0;$i<$contactsArray.length;$i++) {
                        $contactsObject[$contactsArray[$i].contact_id] = $contactsArray[$i];
                    }

                    $Config.set('contacts', $contactsObject);

                    if ($contactsArray.length > 0) {
                        $Messenger.$contactList.setNotEmpty();
                        for (var $i=0;$i<$contactsArray.length;$i++) {
                            $Messenger.$contactList.addContact($contactsArray[$i]);
                        }

                        $contactId = $contactId || $contactsArray[0].contact_id;

                        $Messenger.$contactList.select($contactId, function() {
                            $Messenger.initUpdateTimer();
                            $Messenger.hideLoader();
                        });
                    } else {
                        $Messenger.$contactList.setEmpty();
                    }
                } else {
                    $Tools.log('Error while recieving contacts');
                    window.close();
                }
            });

            $("div.layout-sidebar div.b-list_users ul.list-users").on("click", 'li.list-users_item', function($event) {
                var $this = $(this);
                $Messenger.$contactList.select($this.attr('contact_id'));
            });

            $("div.layout-sidebar div.b-list_users").scroll(function($event) {
                var
                    $this = $(this),
                    $scrollTop = $this.scrollTop(),
                    $scrollHeight = $this.prop('scrollHeight'),
                    $height = $this.height(),
                    $lockKey = 'messenger.contacts.update'
                ;

                if ($height + $scrollTop >= $scrollHeight) {
                    if (!$Messenger.acquireLock($lockKey)) {
                        return false;
                    }

                    $Messenger.$contactList.showLoader();
                    $Tools.ajaxPost('messenger.contacts.get', {'offset': Object.keys($Config.get('contacts')).length - 1}, function($data) {
                        if ($data.status == 0 && $data.message == '') {
                            var
                                $contacts = $data.data.contacts,
                                $contactsObject = {},
                                $item
                            ;

                            for (var $i=0;$i<$contacts.length;$i++) {
                                $contactsObject[$contacts[$i].contact_id] = $contacts[$i];
                            }

                            var $contacts = $Config.get('contacts');
                            for (var $contactId in $contactsObject) {
                                $contacts[$contactId] = $contactsObject[$contactId];
                            }

                            $Config.set('contacts', $contactsObject = $contacts);

                            var $sortArray = [];
                            for (var $contactId in $contactsObject) {
                                $sortArray.push([$contactsObject[$contactId]['changed'], $contactsObject[$contactId]]);
                            }

                            $sortArray.sort(function($a, $b) {
                                return - $a[0] + $b[0];
                            });

                            if ($sortArray.length > 0) {
                                for (var $i=0;$i<$sortArray.length;$i++) {
                                    $item = $sortArray[$i][1];
                                    if ($Messenger.$contactList.exists($item['contact_id'])) {
                                        $Messenger.$contactList.updateContact($item);
                                    } else {
                                        $Messenger.$contactList.addContact($item);
                                    }
                                }
                            }
                        }

                        $Messenger.freeLock($lockKey);
                    }, function() {
                        $Messenger.freeLock($lockKey);
                    }, function() {
                        $Messenger.$contactList.hideLoader();
                    });
                }
            });
        },

        exists: function($contactId) {
            var $item = $("div.layout-sidebar div.b-list_users ul.list-users > li[contact_id=" + $contactId +"]");
            return !!$item.length;
        },

        select: function($contactId, $callback) {
            if (!$Messenger.acquireLock()) {
                return;
            }

            $Messenger.$sendForm.$smilies.hide();

            $Config.set('contact_id', $contactId);

            $("div.layout-sidebar div.b-list_users ul.list-users > li").removeClass('list-users_item-current').removeClass('loading');

            var $item = $("div.layout-sidebar div.b-list_users ul.list-users > li[contact_id=" + $contactId +"]");
            $item.addClass('list-users_item-current');
            $item.addClass('loading');
            $item.removeClass('new-message');

            var $contact = $Config.get('contacts')[$contactId];

            $Messenger.$userInfo.setProfileInfo($contact.platform.info.oid, $contact.platform.info.name);
            $Messenger.$userInfo.setAvatar($contact.platform.info.square_photo_url);
            $Messenger.$userInfo.setAge($contact.platform.info.age);
            $Messenger.$userInfo.setCity($contact.platform.location.city);
            $Messenger.$userInfo.setPhotosCount($contact.platform.info.photos_count);
            $Messenger.$userInfo.setInterests($contact.platform.interests);
            $Messenger.$userInfo.setMeetButtonVisible(!$contact.rated);
            $Messenger.$sendForm.setLimitLockInfo($contact.platform.info.gender);

            $Messenger.$messages.get($contactId, null, function($data) {
                $Messenger.$messages.clear();

                if ($data.messages.length > 0) {
                    $Messenger.$messages.hidePromo();

                    var $messages = $data.messages, $lastMessage;

                    for (var $i=0;$i<$messages.length;$i++) {
                        $Messenger.$messages.addMessage($lastMessage = $messages[$i], true);
                    }

                    if ($data.unread_count > 0) {
                        ($lastMessage['direction'] == 'outbox') &&
                            $Messenger.$messages.setNotReadedStatus();

                        if ($data.unread_count >= 3 && !$data.dialog) {
                            $Messenger.$sendForm.lockByLimit();
                        } else {
                            $Messenger.$sendForm.unlockByLimit();
                            $Messenger.$sendForm.focus();
                        }
                    } else {
                        ($lastMessage['direction'] == 'outbox') &&
                            $Messenger.$messages.setReadedStatus();

                        $Messenger.$sendForm.unlockByLimit();
                        $Messenger.$sendForm.focus();
                    }

                    $Messenger.$messages.scrollDown();
                } else {
                    $Messenger.$messages.showPromo();
                    $Messenger.$sendForm.unlockByLimit();
                    $Messenger.$sendForm.focus();
                }

                $callback && $callback();
                $item.removeClass('loading');
                $Messenger.freeLock();
            }, function (){
                $Messenger.$sendForm.unlockByLimit();
                $Messenger.freeLock();
            });
        },

        /**
         * Маркирует список контактов как пустой
         *
         * @set class
         */
        setEmpty: function() {
            $("div.layout-sidebar").addClass('layout-sidebar_empty');
        },

        /**
         * Маркирует список контактов как НЕпустой
         *
         * @remove class
         */
        setNotEmpty: function() {
            $("div.layout-sidebar").removeClass('layout-sidebar_empty');
        },

        /**
         * Добавление контакта в список контактов
         *
         * @param $contact
         */
        addContact: function($contact) {
            var $html = $(
                '<li class="list-users_item">' +
                    '<span class="status-user"></span>' +
                    '<img class="list-users_avatars"/>' +
                    '<span class="list-users_message"></span>' +
                    '<span class="list-users_state"></span>' +
                    '<span class="list-users_name"></span>' +
                '</li>'
            );

            $html.attr({
                'user_id': $contact.reciever_id,
                'contact_id': $contact.contact_id
            });

            if ($contact.online) {
                $("span.status-user", $html).addClass('status-on').attr('title', 'Сейчас на сайте');
            } else if ($contact.lastaccess) {
                $("span.status-user", $html).addClass('status-off').attr('title', 'Последнее действие: ' + $contact.lastaccess);
            } else {
                $("span.status-user", $html).addClass('status-off').attr('title', 'Не в сети');
            }

            if ($contact.unread_count > 0) {
                $html.addClass('new-message');
                $("span.list-users_message", $html).html($contact.unread_count);
            }

            if ($contact.platform.info.square_photo_url) {
                $("img.list-users_avatars", $html).attr('src', $contact.platform.info.square_photo_url);
            }

            if ($contact.platform.info.name) {
                $("span.list-users_name", $html).html($contact.platform.info.name);
            }

            if ($contact.contact_id == $Config.get('contact_id')) {
                $html.addClass('list-users_item-current');
                $html.removeClass('new-message');
            }

            $html.appendTo($("div.layout-sidebar div.b-list_users ul.list-users"));
        },

        /**
         * Обновляет контакт в списке контактов
         *
         * @param $contact
         */
        updateContact: function($contact) {
            var $html = $("div.layout-sidebar div.b-list_users ul.list-users > li[contact_id=" + $contact['contact_id'] +"]");

            if ($contact.online) {
                $("span.status-user", $html).addClass('status-on').attr('title', 'Сейчас на сайте');
            } else if ($contact.lastaccess) {
                $("span.status-user", $html).addClass('status-off').attr('title', 'Последнее действие: ' + $contact.lastaccess);
            } else {
                $("span.status-user", $html).addClass('status-off').attr('title', 'Не в сети');
            }

            if ($contact.unread_count > 0) {
                $html.addClass('new-message');
                $("span.list-users_message", $html).html($contact.unread_count);
            }

            if ($contact.platform.info.square_photo_url) {
                $("img.list-users_avatars", $html).attr('src', $contact.platform.info.square_photo_url);
            }

            if ($contact.platform.info.name) {
                $("span.list-users_name", $html).html($contact.platform.info.name);
            }

//            if ($contact.contact_id == $Config.get('contact_id')) {
//                $html.addClass('list-users_item-current');
//                $html.removeClass('new-message');
//            }
        }
    },

    /**
     * Инфо пользователя
     *
     * @object
     */
    $userInfo: {

        initUI: function() {
            $(".orange-menu .item.gift").click(function() {
                $(".orange-menu .drop_down.drop_down-present").show();
                $("div.layout-content").addClass('show-present');
                $(".orange-menu .item.gift").addClass('item-current');
                return false;
            });
        },

        $gifts: {

            initUI: function() {
                $(".orange-menu .list-present_item").click(function() {
                    $(".orange-menu .list-present_item").removeClass("list-present_item-selected");
                    $(this).addClass("list-present_item-selected");
                });

                var $sendGiftFunction = function() {
                    var $giftId = $("div.drop_down-present .list-present_item-selected").attr('gift_id');
                    var $comment = $("div.drop_down-present textarea").val();
                    var $currentUserId = $Config.get('contacts')[$Config.get('contact_id')].reciever_id;

                    $Tools.ajaxPost('gift.purchase', {'gift_id': $giftId, 'comment': $comment, 'current_user_id': $currentUserId}, function($data) {
                        if ($data.status == 0 && $data.message == "") {
                            alert('Подарок успешно отправлен :)');
                        } else if ($data.status == 3) {
                            alert('Не хватает сердечек');
                        }

                        var $orangeMenu = $(".orange-menu .drop_down");
                        if ($orangeMenu.is(':visible')) {
                            $orangeMenu.hide();
                            $("div.layout-content").removeClass('show-present');
                            $(".orange-menu .item.gift").removeClass('item-current');
                        }
                    });

                    return false;
                };

                $("div.drop_down-present div.form-send_gift textarea").keypress(function($event) {
                    if ($event.ctrlKey && $event.keyCode == 13) {

                    } else if ($event.keyCode == 13) {
                        return $sendGiftFunction();
                    }
                });

                $("div.drop_down-present div.form-send_gift input[type=submit]").click(function() {
                    return $sendGiftFunction();
                });
            }
        },

        /**
         * Устанавливает аватарку
         *
         * @param $url
         */
        setAvatar: function($url) {
            $("div.window-user_info div.user_info-pic img").attr('src', $url);
        },

        /**
         * Устанавливает метку количества фотографий
         *
         * @param $count
         */
        setPhotosCount: function($count) {
            if ($count) {
                $("div.window-user_info span.user_info-photo i").html($count).show();
            } else {
                $("div.window-user_info span.user_info-photo i").hide();
            }
        },

        /**
         * Устанавливает имя пользователя и ссылку на его анкету
         *
         * @param $id
         * @param $name
         */
        setProfileInfo: function($id, $name) {
            $("div.window-user_info a.user-info_name").html($name).attr('href', $Config.get('platform').partner_url + "app_platform/?action=view&app_id=355&extra=profile" + $id);
            $("div.window-user_info div.user_info-pic a").attr('href', $Config.get('platform').partner_url + "app_platform/?action=view&app_id=355&extra=profile" + $id);
        },

        /**
         * Устанавливает возраст пользователя
         *
         * @param $age
         */
        setAge: function($age) {
            $("div.window-user_info span.user-info_details strong").html($age);
        },

        /**
         * Устанавливает местоположение пользователя
         *
         * @param $city
         */
        setCity: function($city) {
            $("div.window-user_info span.user-info_details span").html(
                ($("div.window-user_info span.user-info_details strong").html() ? ', ' : '') +
                $city
            );
        },

        /**
         * Устанавливает интересы пользователя
         *
         * @param $interests
         */
        setInterests: function($interests) {
            $("div.window-user_info ul.tags-list > *").remove();
            if ($interests.length > 0) {
                for (var $i=0;$i<$interests.length;$i++) {
                    $("div.window-user_info ul.tags-list").append(
                        $('<li class="tags-list_item"></li>').html($interests[$i])
                    )
                }
            }
        },

        setMeetButtonVisible: function($visible) {
            var $giftButton = $("div.window-user_info ul.orange-menu li.item.gift");
            if ($visible) {
                if ($("div.window-user_info ul.orange-menu li.item.meet").length == 0) {
                    var $html = $('<li class="item meet"><a target="_blank"><span>Встретиться</span></a></li>');
                    $('a', $html).attr('href', $Config.get('platform').partner_url + 'app_platform/?action=view&app_id=' + $Config.get('platform').app_id + "&extra=meet" + $Config.get('contacts')[$Config.get('contact_id')].platform.info.oid);
                    $html.insertBefore($giftButton);
                }
            } else {
                $("div.window-user_info ul.orange-menu li.item.meet").remove();
            }
        }
    },

    /**
     * Сообщения
     *
     * @object
     */
    $messages: {

        initUI: function() {
            $(".window-user_message").scroll(function($event) {
                var $scrollTop = $(this).scrollTop();

                if ($scrollTop == 0) {
                    if (!$Messenger.acquireLock()) {
                        return;
                    } else if ($("ul.messages__list li.messages__item[message_id]").length <= 0) {
                        $Messenger.freeLock();
                        return;
                    }

                    $Messenger.$messages.setLoadingStatus();

                    var $messages = $("ul.messages__list li.messages__item[message_id]");
                    if ($messages) {
                        var $lastMessageId = $messages.eq(0).attr('message_id');
                        if ($lastMessageId) {
                            $Messenger.$messages.get($Config.get('contact_id'), $lastMessageId, function($data) {
                                var $messages = $data.messages;
                                $Messenger.$messages.removeLoadingStatus();

                                for (var $i=$messages.length - 1;$i>=0;$i--) {
                                    $Messenger.$messages.addMessage($messages[$i], false);
                                }

                                $Messenger.$messages.scrollTop();
                                $Messenger.freeLock();
                            }, function() {
                                $Messenger.$messages.removeLoadingStatus();
                                $Messenger.freeLock();
                            });
                        } else {
                            $Messenger.freeLock();
                        }
                    } else {
                        $Messenger.freeLock();
                    }
                }
            });

            $("ul.messages__list").on('click', 'li.messages__item.messages__item_promo a', function() {
                $(".orange-menu .drop_down.drop_down-present").show();
                $("div.layout-content").addClass('show-present');
                $(".orange-menu .item.gift").addClass('item-current');
                return false;
            });

            $("div.layout-content div.window-user_message").click(function() {
                $Messenger.$sendForm.$smilies.hide();
            });
        },

        showPromo: function() {
            var $html = $(
                '<li class="messages__item messages__item_promo">' +
                    '<h3 class="lobster">Начните с интересной фразы!</h3>' +
                    '<p></p>' +
                    '<ul class="tags-list"></ul>'+
                    '<div class="messages__item_promo__present"><img></div>' +
                    '<p>А лучше, <a href="#">начните с подарка</a>, ваше сообщение<br>будет не только более заметным, оно произведёт впечатление!</p>' +
                '</li>'
            );

            /** гифт */
            var $gifts = [];
            $("div.drop_down-present img").each(function($i, $el) {
                $gifts.push($($el).attr('src'));
            });

            $("div.messages__item_promo__present img", $html).attr('src', $gifts[$Tools.rand(0, $gifts.length - 1)]);

            /** ищем общие интересы */
            var
                $mutualInterests = [],
                $webUserInterests = $Config.get('webuser').anketa.interests,
                $currentUserInterests = $Config.get('contacts')[$Config.get('contact_id')].platform.interests
            ;

            for (var $i=0;$i<$webUserInterests.length;$i++) {
                if ($currentUserInterests.in_array($webUserInterests[$i])) {
                    $mutualInterests.push($webUserInterests[$i]);
                }
            }

            var $interestsBlock = $("ul.tags-list", $html), $text = $("p", $html).eq(0), $userInfo = $Config.get('contacts')[$Config.get('contact_id')].platform;
            if ($mutualInterests.length > 0) {
                $text.html('Произведите хорошее впечатление!<br>У вас ' + $mutualInterests.length + ' общих интереса с пользователем ' + $userInfo.info.name);
                for (var $i=0;$i<$mutualInterests.length;$i++) {
                    $interestsBlock.append($('<li class="tags-list_item">' + $mutualInterests[$i] + '</li>'));
                }
            } else {
                $interestsBlock.hide();
                $text.html("У нас есть подсказка:<br>" + $userInfo.info.name + ' еще не написал' + ($userInfo.info.gender == 'F' ? 'а' : '') + ' о своих увлечениях,<br>спросите чем он'+($userInfo.info.gender == 'F' ? 'а' : '')+' увлекается');
            }

            $Messenger.$messages.clear();

            var $messagesList = $("ul.messages__list");
            $messagesList.removeClass('messages__item_promo_hide').append($html);
        },

        hidePromo: function() {
            $("ul.messages__list li.messages__item.messages__item_promo").remove();
            $("ul.messages__list").addClass('messages__item_promo_hide');
        },

        setNotReadedStatus: function() {
            $Messenger.$messages.removeReadedStatus();

            var $html = $(
                '<li class="messages__item messages__item_status notreaded">' +
                    '<img class="messages__item__icon" src="/bundles/encounters/images/icon_pending.png" alt="">Сообщение ещё не прочитано' +
                '</li>'
            );

            $("ul.messages__list").append($html);
        },

        setReadedStatus: function() {
            $Messenger.$messages.removeNotReadedStatus();

            var $html = $(
                '<li class="messages__item messages__item_status readed">' +
                    '<img class="messages__item__icon" src="/bundles/encounters/images/icon_completed.png" alt="">Сообщение прочитано' +
                '</li>'
            );

            $("ul.messages__list").append($html);
        },

        setLoadingStatus: function() {
            var $html = $(
                '<li class="messages__item messages__item_status loading">' +
                    '<img class="messages__item__icon" src="/bundles/encounters/images/icon_spin_white.gif" alt="">Загрузка сообщений' +
                '</li>'
            );

            $html.insertBefore($("ul.messages__list li.messages__item").eq(0));
        },

        removeLoadingStatus: function() {
            $("ul.messages__list li.messages__item.messages__item_status.loading").remove();
        },

        removeStatus: function() {
            $("ul.messages__list li.messages__item.messages__item_status").remove();
        },

        removeReadedStatus: function() {
            $("ul.messages__list li.messages__item.messages__item_status.readed").remove();
        },

        removeNotReadedStatus: function() {
            $("ul.messages__list li.messages__item.messages__item_status.notreaded").remove();
        },

        addMessage: function($message, $directMode) {
            if ($message.type == 'text') {
                var $html = $(
                    '<li class="messages__item">' +
                        '<h3 class="messages__header">' +
                        '<span class="messages__name"></span> ' +
                        '<span class="messages__details"></span>' +
                        '</h3>' +
                        '<div class="messages__content"></div>' +
                    '</li>'
                );
                $html.attr('message_id', $message.message_id);

                if ($message.direction == 'to') {
                    $("span.messages__name", $html).html($Config.get('contacts')[$message.contact_id].platform.info.name);
                    $("span.messages__details", $html).html($message.date);
                    $html.addClass('messages__item_next');
                } else {
                    $("span.messages__name", $html).html($Config.get('webuser').anketa.info.name);
                    $("span.messages__details", $html).html($message.date);
                    $html.addClass('messages__item_my');
                }

                $("div.messages__content", $html).html($message.message);
            } else if ($message.type == 'gift') {
                var $html = $(
                    '<li class="messages__item">' +
                        '<h3 class="messages__header">' +
                            '<span class="messages__name"></span> ' +
                            '<span class="messages__details"></span>' +
                        '</h3>' +
                        '<div class="messages__content">' +
                            '<div class="baloon">' +
                                '<img src="" class="baloon_present-i">' +
                                    '<span class="baloon_content">' +
                                        '<i class="baloon_arrow"></i>' +
                                        '<i class="baloon_lenta"></i>' +
                                        '<span class="baloon_wrap">' +
                                            '<span class="baloon_content-text"></span>' +
                                            '<span class="baloon_content-btn"><a href="#" class="button">Отправить в ответ</a></span>' +
                                        '</span>' +
                                    '</span>' +
                                '</div>' +
                            '</div>' +
                        '</li>'
                );

                if ($message.direction == 'to') {
                    $("span.messages__name", $html).html($Config.get('contacts')[$message.contact_id].platform.info.name);
                    $("span.messages__details", $html).html((($Config.get('contacts')[$message.contact_id].platform.info.gender == 'F') ? 'отправила подарок' : 'отправил подарок') + ' ' + $message.date);
                    $html.addClass('messages__item_next');
                } else {
                    $("span.messages__name", $html).html($Config.get('webuser').anketa.info.name);
                    $("span.messages__details", $html).html($message.date);

                    $("span.baloon_content-btn", $html).hide();
                    $html.addClass('messages__item_my');
                }

                if ($message.gift) {
                    $("div.messages__content img", $html).attr('src', $message.gift.url);
                    if ($message.gift.comment) {
                        $("div.messages__content span.baloon_content-text", $html).html($message.gift.comment);
                    } else  {
                        $("div.messages__content span.baloon_content-text", $html).html('...');
                    }
                }
            } else if ($message.type == 'rating') {

            }

            $Messenger.$messages.hidePromo();

            var $messagesList = $("ul.messages__list");
            if ($directMode) {
                $messagesList.append($html);
            } else {
                var $messages = $("li.messages__item[message_id]", $messagesList);
                if ($messages.length > 0) {
                    $html.insertBefore($messages.eq(0));
                }
            }
        },

        clear: function() {
            $("ul.messages__list li.messages__item").remove();
        },

        get: function($contactId, $lastMessageId, $successCallback, $errorCallback) {
            var $params = {'contact_id': $contactId};
            if ($lastMessageId) {
                $params.last_message_id = $lastMessageId;
            }

            $Tools.ajaxPost('messenger.messages.get', $params, function($data) {
                $successCallback($data.data);
            }, $errorCallback);
        },

        scrollDown: function() {
            $(".window-user_message").scrollTop($(".window-user_message").prop('scrollHeight'));
        },

        scrollTop: function() {
            $(".window-user_message").scrollTop(0);
        }
    },

    /**
     * Форма отправки сообщения
     *
     * @object
     */
    $sendForm: {

        /**
         * Init UI
         *
         * @init UI
         */
        initUI: function() {

            var $sendMessage = function() {
                var $textarea = $("div.window-user_form div.input_i");
                var $message = $textarea.html();

                if ($message && $message!='<br>') {
                    $Messenger.$sendForm.$smilies.hide();

                    $Messenger.$sendForm.sendMessage($message, function($data) {
                        var $message = $data.message;

                        $Messenger.$messages.removeStatus();
                        $Messenger.$messages.addMessage($message, true);

                        if ($data.unread_count > 0) {
                            $Messenger.$messages.setNotReadedStatus();

                            if ($data.unread_count >= 3 && !$data.dialog) {
                                $Messenger.$sendForm.lockByLimit();
                            }
                        } else {
                            $Messenger.$messages.setReadedStatus();
                        }

                        $Messenger.$messages.scrollDown();

                        $Messenger.$sendForm.clear();
                        $Messenger.$sendForm.focus();
                    }, function() {
                        $Messenger.$sendForm.focus();
                    });
                }

                return false;
            };

            $("div.window-user_form input[type=submit]").click(function() {
                return $sendMessage();
            });

            $("div.window-user_form div.input_i").keypress(function($event){
                if ($event.ctrlKey && $event.keyCode == 13) {

                } else if ($event.keyCode == 13) {
                    return $sendMessage();
                }
            }).keyup(function() {
                $Tools.saveSelection();
            }).mouseup(function() {
                $Tools.saveSelection();
            }).focus(function(){
                $Tools.restoreSelection();
                return false;
            }).click(function() {
                return false;
            });

            $("div.window-user_form div.form_i").click(function() {
               $Messenger.$sendForm.$smilies.hide();
            });

            $("div.layout-content div.window-user_form div.b-user_disabled span.sent").click(function() {
                $(".orange-menu .drop_down.drop_down-present").show();
                $("div.layout-content").addClass('show-present');
                $(".orange-menu .item.gift").addClass('item-current');
                return false;
            });

            $Messenger.$sendForm.$smilies.initUI();
        },

        setLimitLockInfo: function($gender) {
            var $lockScreen = $("div.window-user_form div.b-user_disabled");
            var $first = $("span.first", $lockScreen);
            var $second = $("span.second", $lockScreen);
            var $third = $("span.third", $lockScreen);
            var $send  = $("span.sent", $lockScreen);

            if ($gender == 'F') {
                $first.html('ей');
                $second.html('она');
                $third.html('ней');
                $send.html('отправив ей милый презент');
            } else if ($gender == 'M') {
                $first.html('ему');
                $second.html('он');
                $third.html('ним');
                $send.html('отправив ему милый презент');
            }
        },

        lockByLimit: function() {
            var $sendForm = $("div.window-user_form");
            !$sendForm.hasClass('disabled-message') && $sendForm.addClass('disabled-message');
        },

        unlockByLimit: function() {
            $("div.window-user_form").removeClass('disabled-message');
        },

        focus: function() {
            $("div.window-user_form div.input_i").focus();
        },

        clear: function() {
            $("div.window-user_form div.input_i").html('');
        },

        /**
         * Отправить сообщение
         *
         * @param str $message
         * @param function $callback
         */
        sendMessage: function($message, $successCallback, $errorCallback) {
            $Tools.ajaxPost('messenger.message.send', {
                'contact_id': $Config.get('contact_id'),
                'message': $message
            }, function($data) {
                if ($data.status == 0 && $data.message == '' && Object.keys($data.data).length > 0) {
                    $successCallback && $successCallback($data.data);
                } else {
                    $errorCallback && $errorCallback();
                }
            }, function() {
                $errorCallback && $errorCallback();
            });
        },

        /**
         * Смайлики
         *
         * @object
         */
        $smilies: {

            initUI: function() {
                $("span.b-smile").click(function() {
                    var $smiliesPopup = $("div.b-pop_smile");

                    if ($smiliesPopup.css('display') != 'none') {
                        $Messenger.$sendForm.$smilies.hide();
                    } else {
                        $Messenger.$sendForm.$smilies.show();
                    }

                    return false;
                }).mousedown(function() {
                    var $textarea = $("div.window-user_form div.input_i");
                    if (!$textarea.is(':focus')) {
                        $textarea.focus();
                    }
                    return false;
                });

                $("div.b-pop_smile").click(function() {
                    return false;
                });

                $("div.b-pop_smile li.list-smile_item").click(function() {
                    var $className = $('i', this).attr('class').split(/\s+/)[1];
                    document.execCommand('insertHTML', false, '&nbsp;<img src="/bundles/encounters/images/pixel.gif" class="smile ' + $className +'"/>&nbsp;');
                    $Tools.saveSelection();
                    return false;
                });
            },

            hide: function() {
                $("div.b-pop_smile").fadeOut('fast');
            },

            show: function() {
                $("div.b-pop_smile").fadeIn('fast');
            }
        },
    }
}