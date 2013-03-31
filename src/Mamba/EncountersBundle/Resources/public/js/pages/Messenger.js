/**
 * Messenger
 *
 * @author shpizel
 */
$Messenger = {

    /**
     * Инициализация интерфейса
     *
     * @init
     */
    initUI: function() {
        if ($Config.get('debug')) {
            window.setTimeout(function() {
                $("div.sf-toolbarreset").parent().hide();
            }, 500);
        }

        $Messenger.$contactList.initUI();
        $Messenger.$sendForm.initUI();
        $Messenger.$smilies.initUI();
    },

    /**
     * Запуск страницы
     *
     * @run page
     */
    run: function() {
        //$Messenger.hideLoader();
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

        initUI: function() {
            $.post($Routing.getPath('messenger.contacts.get'), function($data) {
                if ($data.status == 0 && $data.message == '') {
                    var $contacts = $data.data.contacts;
                    var $contactsKeys = Object.keys($contacts);

                    if ($contactsKeys.length > 0) {
                        $Messenger.$contactList.setNotEmpty();
                        for (var $i=0;$i<$contactsKeys.length;$i++) {
                            $Messenger.$contactList.addContact($contacts[$contactsKeys[$i]]);
                        }

                        $Messenger.$contactList.select($contacts[$contactsKeys[0]].contact_id, function() {
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
        },

        select: function($contactId, $callback) {
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

            $Messenger.$messages.get($contactId, function($messages) {
                $Messenger.$messages.clear();

                for (var $i=0;$i<$messages.length;$i++) {
                    $Messenger.$messages.addMessage($messages[$i]);
                }

                $Messenger.$messages.scrollDown();
                $callback && $callback();

                $item.removeClass('loading');
            }, function (){

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
                    '<span class="status-user status-on" title="Сейчас на сайте"></span>' +
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


            if ($contact.unread_count >= 0) {
                $html.addClass('new-message');
                $("span.list-users_message", $html).html($contact.unread_count);
            }

            if ($contact.platform.info.square_photo_url) {
                $("img.list-users_avatars", $html).attr('src', $contact.platform.info.square_photo_url);
            }

            if ($contact.platform.info.name) {
                $("span.list-users_name", $html).html($contact.platform.info.name);
            }

            $html.appendTo($("div.layout-sidebar div.b-list_users ul.list-users"));
        },

        /**
         * Обновляет контакт в списке контактов
         *
         * @param $contact
         */
        updateContact: function($contact) {

        }
    },

    /**
     * Инфо пользователя
     *
     * @object
     */
    $userInfo: {

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
        }
    },

    /**
     * Сообщения
     *
     * @object
     */
    $messages: {

        initUI: function() {

        },

        setNotReadedStatus: function() {
            $Messenger.$messages.removeStatus();

            var $html = $(
                '<li class="messages__item messages__item_status">' +
                    '<img class="messages__item__icon" src="/bundles/encounters/images/icon_pending.png" alt="">Сообщение ещё не прочитано' +
                '</li>'
            );
        },

        setReadedStatus: function() {
            $Messenger.$messages.removeStatus();

            var $html = $(
                '<li class="messages__item messages__item_status">' +
                    '<img class="messages__item__icon" src="/bundles/encounters/images/icon_completed.png" alt="">Сообщение прочитано' +
                '</li>'
            );
        },

        setLoadingStatus: function() {
            $Messenger.$messages.removeStatus();

            var $html = $(
                '<li class="messages__item messages__item_status">' +
                    '<img class="messages__item__icon" src="/bundles/encounters/images/icon_spin_white.gif" alt="">Загрузка сообщений' +
                '</li>'
            );
        },

        removeStatus: function() {
            $("div.messages__content li.messages__item.messages__item_status").remove();
        },

        addMessage: function($message) {
            var $html = $(
                '<li class="messages__item">' +
                    '<h3 class="messages__header">' +
                        '<span class="messages__name"></span> ' +
                        '<span class="messages__details"></span>' +
                    '</h3>' +
                    '<div class="messages__content"></div>' +
                '</li>'
            );

            if ($message.type == 'text') {
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

            } else if ($message.type == 'rating') {

            }

            var $messagesList = $("ul.messages__list");
            $messagesList.append($html);
        },

        clear: function() {
            $("ul.messages__list li.messages__item").remove();
        },

        get: function($contactId, $successCallback, $errorCallback) {
            $.post($Routing.getPath('messenger.messages.get'), {'contact_id': $contactId}, function($data) {
                $successCallback($data.data);
            }).error($errorCallback);
        },

        scrollDown: function() {
            $(".window-user_message").scrollTop($(".window-user_message").prop('scrollHeight'));
        }
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
                    $("div.b-pop_smile").fadeOut('fast');
                } else {
                    $("div.b-pop_smile").fadeIn('fast');
                }
            });
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
            $("div.window-user_form input[type=submit]").click(function() {
                var $textarea = $("div.window-user_form div.input_i");
                var $message = $textarea.html();

                if ($message && $message!='<br>') {
                    $Messenger.$sendForm.sendMessage($message, function() {
                        $Messenger.$sendForm.clear();
                        $Messenger.$sendForm.focus();
                    }, function() {
                        $Messenger.$sendForm.focus();
                    });
                }

                return false;
            });

            $("div.window-user_form div.input_i").keypress(function($event){
                if ($event.ctrlKey && $event.keyCode == 13) {
                    alert('ctrl + ent');
                } else if ($event.keyCode == 13) {
                    var $message = $("div.window-user_form div.input_i").html();
                    if ($message && $message!='<br>') {
                        $Messenger.$sendForm.sendMessage($message, function() {
                            $Messenger.$sendForm.clear();
                            $Messenger.$sendForm.focus();
                        }, function() {
                            $Messenger.$sendForm.focus();
                        });
                    }

                    return false;
                }
            });
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
            $.post($Routing.getPath('messenger.message.send'), {
                'contact_id': $Config.get('contact_id'),
                'message': $message
            }, function($data) {
                if ($data.status == 0 && $data.message == '') {
                    $Messenger.$messages.addMessage($data.data);
                    $Messenger.$messages.scrollDown();
                    $successCallback && $successCallback();
                } else {
                    $errorCallback && $errorCallback();
                }
            }).error(function() {
                $errorCallback && $errorCallback();
            });
        }
    }
}