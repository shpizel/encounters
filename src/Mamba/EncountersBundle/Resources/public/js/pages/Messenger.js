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
            $Messenger.$userInfo.$gifts.hideLayer();
        }).keydown(function($event) {
            if ($event.ctrlKey || $event.metaKey) {
                return;
            }

            if ($Messenger.$userInfo.$gifts.active) {
                var $texarea = $("div.drop_down-present textarea");
                if (!$texarea.is(":focus")) {
                    $texarea.focus();
                }
            } else if (!$Messenger.$sendForm.isFocused()) {
                $Messenger.$sendForm.focus();
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

        /**
         * Init UI
         *
         * @init UI
         */
        initUI: function() {
            var $contactId = $Config.get('contact_id'), $postData = {'online': true};
            if ($contactId) {
                $postData['contact_id'] = $contactId;
            }

            $Tools.ajaxPost('messenger.contacts.get', $postData, function($data) {
                if ($data.status == 0 && $data.message == '') {
                    var
                        $onlineArray = $data.data.online,
                        $contactsArray = $data.data.contacts,
                        $contactsObject = {}
                    ;

                    if ($onlineArray.length > 0) {
                        $Config.set('online', $onlineArray);
                        $Messenger.$contactList.$onlineUsers.initUI();
                    }

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
                            $Messenger.hideLoader();
                        });
                    } else {
                        $Messenger.$contactList.setEmpty();
                        $Messenger.$contactList.$onlineUsers.select();
                        $Messenger.hideLoader();
                    }

                    $Messenger.$contactList.initUpdateTimer();

                } else {
                    $Tools.log('Error while recieving contacts');
                    window.close();
                }
            }, function() {
                $Tools.log('Error while recieving contacts');
                window.close();
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

            $("div.b-list_users").slimScroll({
                height: '100%'
            });
        },

        showLoader: function() {
            $("div.b-list_users div.b-list_loading").css('visibility', 'visible');
        },

        hideLoader: function() {
            $("div.b-list_users div.b-list_loading").css('visibility', 'hidden');
        },

        exists: function($contactId) {
            var $item = $("div.layout-sidebar div.b-list_users ul.list-users > li[contact_id=" + $contactId +"]");
            return !!$item.length;
        },

        deselect: function() {
            $("div.layout-sidebar div.b-list_users ul.list-users > li").removeClass('list-users_item-current').removeClass('loading');
        },

        select: function($contactId, $callback) {
//            if (!$Messenger.acquireLock()) {
//                return false;
//            }

            var $currentContactId = $Config.get('contact_id');

            $Config.set('contact_id', $contactId);

            $Messenger.$contactList.deselect();
            $Messenger.$contactList.$onlineUsers.setInactiveStatus();
            $Messenger.$contactList.$onlineUsers.hideOnlineUsersSelector();

            var $item = $("div.layout-sidebar div.b-list_users ul.list-users > li[contact_id=" + $contactId +"]");
            $item.addClass('list-users_item-current');
            $item.addClass('loading');
            $item.removeClass('new-message');

            var $contact = $Config.get('contacts')[$contactId];

            $Messenger.$userInfo.setProfileInfo($contact.platform.info.user_id, $contact.platform.info.name);
            $Messenger.$userInfo.setAvatar($contact.platform.avatar.square_photo_url);
            $Messenger.$userInfo.setAge($contact.platform.info.age);
            $Messenger.$userInfo.setCity($contact.platform.location.city.name);
            $Messenger.$userInfo.setPhotosCount($contact.platform.info.photos_count);
            $Messenger.$userInfo.setInterests($contact.platform.interests);
            $Messenger.$userInfo.setMeetButtonVisible(!$contact.rated);
            $Messenger.$sendForm.setLimitLockInfo($contact.platform.info.gender);

            var $lastMessageId = null, $itself = false, $lastMessage;
            if (($currentContactId == $contactId)) {
                $lastMessageId = $Messenger.$messages.getLastMessageId();
                $itself = true;
            } else {
                $Messenger.$sendForm.$smilies.hide();
            }

            $Messenger.$messages.get($contactId, null, $lastMessageId, function($data) {
                if ($contactId != $Config.get('contact_id')) {
                    return;
                }

                if (!$itself) {
                    $Messenger.$messages.clear();
                }

                var
                    $messages = $data.messages,
                    $lastMessageKey = "last-message-by-" + $contactId
                ;

                if ($messages.length > 0) {
                    if ($itself) {
                        $Messenger.$messages.removeStatus();
                    }

                    if (!$lastMessageId) {
                        $Messenger.$messages.hidePromo();
                    }

                    for (var $i=0;$i<$messages.length;$i++) {
                        if (!$Messenger.$messages.exists($Config.set($lastMessageKey, $messages[$i]))) {
                            $Messenger.$messages.addMessage($messages[$i], true);
                        }
                    }

                    $lastMessage = $Config.get($lastMessageKey);

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
                } else if (!$Config.get($lastMessageKey)) {
                    $Messenger.$messages.showPromo();
                    $Messenger.$sendForm.unlockByLimit();
                    $Messenger.$sendForm.focus();
                } else {
                    //сообщений нет, но диалог не пустой
                    $lastMessage = $Config.get($lastMessageKey);

                    if ($itself) {
                        $Messenger.$messages.removeStatus();
                    }

                    if ($data.unread_count > 0) {
                        ($lastMessage['direction'] == 'outbox') &&
                            $Messenger.$messages.setNotReadedStatus();

                        if ($data.unread_count >= 3 && !$data.dialog) {
                            $Messenger.$sendForm.lockByLimit();
                        }
                    } else {
                        ($lastMessage['direction'] == 'outbox') &&
                            $Messenger.$messages.setReadedStatus();
                    }
                }

                $callback && $callback();
                $item.removeClass('loading');
                //$Messenger.freeLock();
            }, function (){
                $Messenger.$sendForm.unlockByLimit();
                //$Messenger.freeLock();
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

            if ($contact.platform.avatar.square_photo_url) {
                $("img.list-users_avatars", $html).attr('src', $contact.platform.avatar.square_photo_url);
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

            if ($contact.platform.avatar.square_photo_url) {
                $("img.list-users_avatars", $html).attr('src', $contact.platform.avatar.square_photo_url);
            }

            if ($contact.platform.info.name) {
                $("span.list-users_name", $html).html($contact.platform.info.name);
            }
        },

        initUpdateTimer: function() {
            window.setInterval(
                function() {
                    if (!$Messenger.acquireLock()) return false;

                    $Tools.ajaxPost('messenger.contacts.update', {}, function($data) {
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
                        }

                        $Messenger.freeLock();

                        var $contactId = $Config.get('contact_id');
                        if ($contactId) {
                            if ($Config.get('contacts')[$contactId]['unread_count']) {
                                $Messenger.$contactList.select($contactId);
                            } else if ($Config.get('last-message-by-' + $contactId) && $Config.get('contacts')[$contactId]['online']) {
                                $Messenger.$contactList.select($contactId);
                            }
                        }
                    }, function() {
                        $Messenger.freeLock();
                    });
                },
                $Messenger.$contactList.updateTimeout
            );
        },

        updateTimeout: 5000,

        $onlineUsers: {

            initUI: function() {
                $("div.layout-sidebar div.b-user_online").click(function() {
                    $Messenger.$contactList.$onlineUsers.select();
                });

                /**
                 * Берем до 4х юзеров рандомно и суем их в виджет
                 *
                 * @author shpizel
                 */
                var $onlineUsers = $Config.get('online');
                if ($onlineUsers && $onlineUsers.length > 0) {
                    //$onlineUsers = $Tools.shuffle($onlineUsers);
                    $onlineUsers = $onlineUsers.slice(0, 4);

                    var $menu = $("div.layout-sidebar div.b-user_online ul.list_users");
                    $menu.html('');

                    for (var $i=0;$i<$onlineUsers.length;$i++) {
                        $html = $('<li class="list_users_item"><img height="28" width="28" class="list_users_item-img"></li>');
                        $("img", $html).attr('src', $onlineUsers[$i]['avatar']['square_photo_url']);
                        $html.appendTo($menu);
                    }
                }

                $Messenger.$contactList.$onlineUsers.show();
            },

            hideOnlineUsersSelector: function() {
                $layout = $("div.app_message-layout").removeClass('user-select_show');
            },

            select: function() {
                $Messenger.$contactList.$onlineUsers.setActiveStatus();
                $Messenger.$contactList.$onlineUsers.setLoadingStatus();
                $Messenger.$contactList.deselect();
                $Config.set('contact_id', null);

                var
                    $layout = $("div.app_message-layout")
                    $select = $("div.user-select ul.list-user_select", $layout),
                    $onlineUsers = $Config.get('online')
                ;

                if (!$layout.hasClass('user-select_show') && $onlineUsers.length > 0) {
                    //$onlineUsers = $Tools.shuffle($onlineUsers);

                    $select.html('');

                    for (var $i=0;$i<$onlineUsers.length;$i++) {
                        $html = $(
                            '<li class="list-user_select-item">' +
                                '<img class="users-select__avatar">' +
                                '<div class="user-info">' +
                                    '<div class="inner">' +
                                        '<div class="user-name">Твоя Красотка</div>' +
                                        '<div class="user-location">27, Москва</div>' +
                                        '<a class="button user-add_list">Добавить</a>' +
                                    '</div>' +
                                '</div>' +
                            '</li>'
                        );

                        $("img", $html).attr('src', $onlineUsers[$i]['avatar']['square_photo_url']);
                        $("div.user-name", $html).html($onlineUsers[$i]['info']['name']);
                        $("div.user-location", $html).html($onlineUsers[$i]['info']['age'] + ', ' + $onlineUsers[$i]['location']['city']['name']);
                        $("a.button", $html).attr('href', '/messenger?id=' + $onlineUsers[$i]['info']['user_id']);
                        $html.appendTo($select);
                    }

                    $layout.addClass('user-select_show');
                }

                $Messenger.$contactList.$onlineUsers.removeStatus();

            },

            removeStatus: function() {
                $("div.layout-sidebar div.b-user_online").removeClass('loading');
            },

            setLoadingStatus: function() {
                $("div.layout-sidebar div.b-user_online").addClass('loading');
            },

            setActiveStatus: function() {
                $("div.layout-sidebar div.b-user_online").addClass('online-select');
            },

            setInactiveStatus: function() {
                $("div.layout-sidebar div.b-user_online").removeClass('online-select');
            },

            hide: function() {
                $("div.layout-sidebar div.b-user_online").css('visibility', 'hidden');
            },

            show: function() {
                $("div.layout-sidebar div.b-user_online").css('visibility', 'visible');
            }
        }
    },

    /**
     * Инфо пользователя
     *
     * @object
     */
    $userInfo: {

        active: false,

        initUI: function() {
            $(".orange-menu .item.gift").click(function() {
                $Messenger.$userInfo.$gifts.showLayer();
                return false;
            });
        },

        $gifts: {

            initUI: function() {
                $(".orange-menu .list-present_item").click(function() {
                    $(".orange-menu .list-present_item").removeClass("list-present_item-selected");
                    $(this).addClass("list-present_item-selected");
                    $("div.drop_down-present textarea").focus();
                });

                var $sendGiftFunction = function() {
                    var $giftId = $("div.drop_down-present .list-present_item-selected").attr('gift_id');
                    var $comment = $("div.drop_down-present textarea").val();
                    var $currentUserId = $Config.get('contacts')[$Config.get('contact_id')].reciever_id;

                    var $postData = {'gift[id]': $giftId, 'gift[comment]': $comment, 'current_user_id': $currentUserId};
                    var $lastMessageId = $Messenger.$messages.getLastMessageId();
                    if ($lastMessageId) {
                        $postData['last_message_id'] = $lastMessageId;
                    }

                    if (!$Messenger.acquireLock('sendmessage')) return false;

                    document.body.style.cursor = "wait";
                    $("span.button").addClass('active');
                    $Tools.ajaxPost('messenger.gift.send', $postData, function($data) {
                        if ($data.status == 0 && $data.message == "") {
                            var
                                $messages = $data.data.messages,
                                $lastMessageKey = "last-message-by-" + $Config.get('contact_id'),
                                $data = $data.data,
                                $lastMessage
                            ;

                            $Messenger.$messages.removeStatus();

                            for (var $i=0;$i<$messages.length;$i++) {
                                if (!$Messenger.$messages.exists($Config.set($lastMessageKey, $messages[$i]))) {
                                    $Messenger.$messages.addMessage($messages[$i], true);
                                }
                            }

                            $lastMessage = $Config.get($lastMessageKey);

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
                        } else if ($data.status == 3) {
                            alert('Недостаточно сердечек для покупки подарка! Сердечки можно приобрести в приложении :)');
                        }

                        $Messenger.$userInfo.$gifts.hideLayer();
                        $Messenger.freeLock('sendmessage');

                        document.body.style.cursor = "default";
                        $("span.button").removeClass('active');
                    }, function() {
                        $Messenger.freeLock('sendmessage');
                        document.body.style.cursor = "default";
                        $("span.button").removeClass('active');
                    });

                    return false;
                };

                $("div.drop_down-present div.form-send_gift textarea").keypress(function($event) {
                    if ($event.ctrlKey && $event.keyCode == 13) {

                    } else if ($event.keyCode == 13) {
                        if ($("div.drop_down-present .list-present_item-selected").length > 0) {
                            return $sendGiftFunction();
                        }
                    }
                });

                $("div.drop_down-present div.form-send_gift span.button").click(function() {
                    if ($("div.drop_down-present .list-present_item-selected").length > 0) {
                        return $sendGiftFunction();
                    }

                    return false;
                });

                $("ul.messages__list").on('click', 'div.baloon span.baloon_content-btn', function() {
                    $Messenger.$userInfo.$gifts.showLayer();
                    return false;
                });
            },

            showLayer: function() {
                $(".orange-menu .drop_down.drop_down-present").show();
                $("div.layout-content").addClass('show-present');
                $(".orange-menu .item.gift").addClass('item-current');

                $Messenger.$userInfo.$gifts.active = true;
            },

            hideLayer: function() {
                var $orangeMenu = $(".orange-menu .drop_down");
                if ($orangeMenu.is(':visible')) {
                    $orangeMenu.hide();
                    $("div.layout-content").removeClass('show-present');
                    $(".orange-menu .item.gift").removeClass('item-current');
                }

                $Messenger.$userInfo.$gifts.active = false;
            }
        },

        /**
         * Устанавливает аватарку
         *
         * @param $url
         */
        setAvatar: function($url) {
            var $img = $("div.window-user_info div.user_info-pic img");
            $img.attr('src', $url || '/bundles/encounters/images/pixel.gif');
        },

        /**
         * Устанавливает метку количества фотографий
         *
         * @param $count
         */
        setPhotosCount: function($count) {
            var
                $span = $("div.window-user_info span.user_info-photo"),
                $i = $("i", $span)
            ;

            if ($count) {
                $i.html($count);
                $span.show();
            } else {
                $span.hide();
            }
        },

        /**
         * Устанавливает имя пользователя и ссылку на его анкету
         *
         * @param $id
         * @param $name
         */
        setProfileInfo: function($id, $name) {
            $("div.window-user_info a.user-info_name").html($name).attr('href', $Config.get('platform').partner_url + "app_platform/?action=view&app_id=" + $Config.get('platform').app_id + "&extra=profile" + $id);
            $("div.window-user_info div.user_info-pic a").attr('href', $Config.get('platform').partner_url + "app_platform/?action=view&app_id=" + $Config.get('platform').app_id + "&extra=profile" + $id);
        },

        /**
         * Устанавливает возраст пользователя
         *
         * @param $age
         */
        setAge: function($age) {
            $("div.window-user_info span.user-info_details strong").html(($age > 0) ? $age : '');
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
                    $('a', $html).attr('href', $Config.get('platform').partner_url + 'app_platform/?action=view&app_id=' + $Config.get('platform').app_id + "&extra=meet" + $Config.get('contacts')[$Config.get('contact_id')].platform.info.user_id);
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
                var $scrollTop = $(this).scrollTop(), $firstMessageId;

                if ($scrollTop == 0) {
                    if (!$Messenger.acquireLock()) {
                        return false;
                    } else if (!($firstMessageId = $Messenger.$messages.getFirstMessageId())) {
                        $Messenger.freeLock();
                        return;
                    }

                    $Messenger.$messages.setLoadingStatus();

                    if ($firstMessageId) {
                        $Messenger.$messages.get($Config.get('contact_id'), $firstMessageId, null, function($data) {
                            var $messages = $data.messages;
                            $Messenger.$messages.removeLoadingStatus();

                            for (var $i=$messages.length - 1;$i>=0;$i--) {
                                $Messenger.$messages.addMessage($messages[$i], false);
                            }

                            $messages.length > 0 && $Messenger.$messages.scrollTop(5);
                            $Messenger.freeLock();
                        }, function() {
                            $Messenger.$messages.removeLoadingStatus();
                            $Messenger.freeLock();
                        });
                    } else {
                        $Messenger.freeLock();
                    }
                }
            });

            $("ul.messages__list").on('click', 'li.messages__item.messages__item_promo a', function() {
                $Messenger.$userInfo.$gifts.showLayer();
                return false;
            });

            $("div.layout-content div.window-user_message").click(function() {
                $Messenger.$sendForm.$smilies.hide();
            });
        },

        getLastMessageId: function() {
            var $lastMessage = $("ul.messages__list li.messages__item:not(.messages__item_status):last");
            if ($lastMessage.length > 0) {
                return $lastMessage.attr('message_id');
            }
        },

        getFirstMessageId: function() {
            var firstMessage = $("ul.messages__list li.messages__item:not(.messages__item_status):first");
            if (firstMessage.length > 0) {
                return firstMessage.attr('message_id');
            }
        },

        exists: function($message) {
            return $("ul.messages__list li.messages__item[message_id=" + $message.message_id + "]").length > 0;
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
                if ($currentUserInterests.length == 0) {
                    $interestsBlock.hide();
                    $text.html("У нас есть подсказка:<br>" + $userInfo.info.name + ' еще не написал' + ($userInfo.info.gender == 'F' ? 'а' : '') + ' о своих увлечениях,<br>спросите чем он'+($userInfo.info.gender == 'F' ? 'а' : '')+' увлекается');
                } else {
                    $currentUserInterests = $Tools.shuffle($currentUserInterests);
                    $text.html('Произведите хорошее впечатление!<br> ' + $userInfo.info.name + ' отметил' + ($userInfo.info.gender == 'F' ? 'а' : '') + ' свои увлечения');
                    for (var $i=0;$i<(($currentUserInterests.length > 3) ? 3 : $currentUserInterests.length);$i++) {
                        $interestsBlock.append($('<li class="tags-list_item">' + $currentUserInterests[$i] + '</li>'));
                    }
                }
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

                if ($message.direction == 'inbox') {
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

                $html.attr('message_id', $message.message_id);

                if ($message.direction == 'outbox') {
                    $("span.messages__name", $html).html($Config.get('webuser').anketa.info.name);
                    $("span.messages__details", $html).html($message.date);

                    $("span.baloon_content-btn", $html).hide();
                    $html.addClass('messages__item_my');
                } else {
                    $("span.messages__name", $html).html($Config.get('contacts')[$message.contact_id].platform.info.name);
                    $("span.messages__details", $html).html((($Config.get('contacts')[$message.contact_id].platform.info.gender == 'F') ? 'отправила подарок' : 'отправил подарок') + ' ' + $message.date);
                    $html.addClass('messages__item_next');
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
                /**
                 * TODO:
                 *
                 * @author shpizel
                 */
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

        get: function($contactId, $firstMessageId, $lastMessageId, $successCallback, $errorCallback) {
            var $postData = {'contact_id': $contactId};
            if ($firstMessageId) {
                $postData['first_message_id'] = $firstMessageId;
            } else if ($lastMessageId) {
                $postData['last_message_id'] = $lastMessageId;
            }

            $Tools.ajaxPost('messenger.messages.get', $postData, function($data) {
                $successCallback($data.data);
            }, $errorCallback);
        },

        scrollDown: function() {
            $(".window-user_message").scrollTop($(".window-user_message").prop('scrollHeight'));
        },

        scrollTop: function($top) {
            $(".window-user_message").scrollTop($top || 0);
        }
    },

    /**
     * Форма отправки сообщения
     *
     * @var Object
     */
    $sendForm: {

        initUI: function() {

            var $sendMessage = function() {
                var $textarea = $("div.window-user_form div.input_i");
                var $message = $textarea.html();

                if ($message && $.trim($message.toLowerCase())!='<br>') {
                    $Messenger.$sendForm.$smilies.hide();

                    if (!$Messenger.acquireLock('sendmessage')) return false;

                    document.body.style.cursor = "wait";
                    $("span.button").addClass('active');
                    $Messenger.$sendForm.sendMessage($message, function($data) {
                        var
                            $messages = $data.messages,
                            $lastMessageKey = "last-message-by-" + $Config.get('contact_id'),
                            $lastMessage
                        ;

                        $Messenger.$messages.removeStatus();

                        for (var $i=0;$i<$messages.length;$i++) {
                            $lastMessage = $messages[$i];

                            if (!$Messenger.$messages.exists($Config.set($lastMessageKey, $messages[$i]))) {
                                $Messenger.$messages.addMessage($messages[$i], true);
                            }
                        }

                        $lastMessage = $Config.get($lastMessageKey);

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
                        $Messenger.$sendForm.clear();
                        $Messenger.freeLock('sendmessage');
                        document.body.style.cursor = "default";
                        $("span.button").removeClass('active');
                    }, function() {
                        $Messenger.$sendForm.focus();
                        $Messenger.freeLock('sendmessage');
                        document.body.style.cursor = "default";
                        $("span.button").removeClass('active');
                    });
                } else {
                    $Messenger.$sendForm.focus();
                }

                return false;
            };

            $("div.window-user_form span.button").click(function() {
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
            }).click(function() {
                return false;
            });

            $("div.window-user_form div.form_i").click(function() {
               $Messenger.$sendForm.$smilies.hide();
            });

            $("div.layout-content div.window-user_form div.b-user_disabled span.sent").click(function() {
                $Messenger.$userInfo.$gifts.showLayer();
                return false;
            });

            $Messenger.$sendForm.$smilies.initUI();
        },

        getHTML: function() {
            return $("div.window-user_form div.input_i").html();
        },

        setLimitLockInfo: function($gender) {
            var
                $lockScreen = $("div.window-user_form div.b-user_disabled"),
                $first = $("span.first", $lockScreen),
                $second = $("span.second", $lockScreen),
                $third = $("span.third", $lockScreen),
                $send  = $("span.sent", $lockScreen)
            ;

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

            !$sendForm.hasClass('disabled-message') &&
                $sendForm.addClass('disabled-message');
        },

        unlockByLimit: function() {
            $("div.window-user_form").removeClass('disabled-message');
        },

        focus: function() {
            var $textarea = $("div.window-user_form div.input_i");
            if (!$textarea.is(":focus")) {
                $textarea.focus();
            }
        },

        clear: function() {
            $("div.window-user_form div.input_i").html('<br/>');
        },

        sendMessage: function($message, $successCallback, $errorCallback) {
            var
                $postData = {
                    'contact_id': $Config.get('contact_id'),
                    'message': $message
                },
                $lastMessageId = $Messenger.$messages.getLastMessageId()
            ;

            if ($lastMessageId) {
                $postData['last_message_id'] = $lastMessageId;
            }

            $Tools.ajaxPost('messenger.message.send', $postData, function($data) {
                if ($data.status == 0 && $data.message == ''/* && Object.keys($data.data).length > 0*/) {
                    $successCallback && $successCallback($data.data);
                } else {
                    $errorCallback && $errorCallback();
                }
            }, function() {
                $errorCallback && $errorCallback();
            });
        },

        isFocused: function() {
            var $textarea = $("div.window-user_form div.input_i");
            if ($textarea.is(':focus')) {
                return true;
            }

            return false;
        },

        /**
         * Смайлики
         *
         * @var Object
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
                    !$Messenger.$sendForm.isFocused() &&
                        $Messenger.$sendForm.focus();

                    return false;
                });

                $("div.b-pop_smile").click(function() {
                    return false;
                });

                $("div.b-pop_smile li.list-smile_item").click(function() {
                    var
                        $className = $('i', this).attr('class').split(/\s+/)[1],
                        $html = '&nbsp;<img src="/bundles/encounters/images/pixel.gif" class="smile ' + $className +'"/>&nbsp;',
                        $textarea = $("div.window-user_form div.input_i")
                    ;

                    if (!$textarea.is(':focus')) {
                        $textarea.focus();
                    }

                    if ($.browser.msie) {
                        var $ieRange = document.selection.createRange();
                        if ($ieRange.pasteHTML) {
                            $ieRange.pasteHTML($html);
                        }
                    } else {
                        document.execCommand('insertHTML', false, $html);
                    }


                    $Tools.saveSelection();
                    return false;
                });

                $("div.b-pop_smile ul.list-smile").slimScroll({height: '100%'});
            },

            hide: function() {
                $("div.b-pop_smile").fadeOut('fast');
            },

            show: function() {
                $("div.b-pop_smile").fadeIn('fast');
            }
        }
    }
}