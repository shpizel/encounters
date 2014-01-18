/**
 * Search
 *
 * @author shpizel
 */
$Search = {

    /**
     * Storage
     *
     * @var object
     */
    $storage: {

    },

    /**
     * Инициализация интерфейса
     *
     * @return null
     */
    initUI: function() {
        this.initDecisionButtons();
        $("img.app-image").click(function() {
            $Search.listNextPhoto();
        });

        var $image = $("div.app-image-member img.app-image")[0];
        $image.onload = function() {
            window.clearTimeout(window.$loadingTimeout);
            $(this).fadeTo('fast', 1);
        }

        $image.onerror = function() {
            window.clearTimeout(window.$loadingTimeout);
            $(this).fadeTo('fast', 1);
        }

        var $showLayerFunction = function() {
            $Tools.ajaxPost('decision.get', { user_id: $Search.$storage['currentQueueElement']['info']['id']}, function($data) {
                if ($data.status == 0 && $data.message == "") {
                    $data = $data.data;
                    if ($data.hasOwnProperty('charge')) {
                        $Battery.setCharge($data.charge);
                    }

                    if ($data.hasOwnProperty('account')) {
                        $Account.setAccount($data.account);
                    }

                    if ($data.decision === false) {
                        $Layers.showAnswerNotSeeYetLayer();
                    } else if ($data.decision == -1) {
                        $Layers.showAnswerNoLayer();
                    } else if ($data.decision == 0) {
                        $Layers.showAnswerMaybeLayer();
                    } else if ($data.decision == 1) {
                        $Layers.showAnswerYesLayer();
                    }
                } else if ($data.status == 3) {
                    $Layers.showEnergyLayer();
                }
            });

            return false;
        };

        $("p.app-see-block a").click($showLayerFunction);
        $("div.app-image-member div.name-container div.content a").click($showLayerFunction);

        $("div.app-block-no-popular a.close").click(function() {
            $Tools.ajaxPost('variable.set', { key: 'search_no_popular_block_hidden', data: 1}, function($data) {
                if ($data.status == 0 && $data.message == "") {
                    $("div.app-block-no-popular").hide();
                }
            });
        });
    },

    /**
     * Залочить UI
     *
     * @lock UI
     */
    lockUI: function() {
        $("div.app-meet-button").fadeTo('fast', 0.6);
        $("div.app-lenta-img").hide();
        $("div.name-container").hide();
        $("p.app-see-block").fadeTo('fast', 0.6);
        $("div.app-image-member img.app-image").hide();
        $("a.rarr").hide();
        $("a.larr").hide();
        $Search.$storage['locked'] = true;
    },

    /**
     * Разлочить UI
     *
     * @unlock UI
     */
    unlockUI: function() {
        $Search.$storage['locked'] = false;
        $("div.app-meet-button > a.app-menu1").removeClass("app-menu1-active");
        $("div.app-meet-button > a.app-menu2").removeClass("app-menu2-active");
        $("div.app-meet-button > a.app-menu3").removeClass("app-menu3-active");

        this.showNextPhoto();

        /**
         * Реклама мордоленты
         *
         * @author shpizel
         */
        /*if (($Config.get('webuser')['account'] > 0) && (!$Tools.getCookie('photoline-purchase-layer-auto-show'))) {
            $Layers.showPhotolinePurchaseLayer();
            $Tools.setCookie('photoline-purchase-layer-auto-show', true, {expires: new Date((new Date()).getTime() + (8 * 60 * 60 * 1000))});
        }*/
    },

    /**
     * Инициализирует кнопки
     *
     * @init decision buttons
     */
    initDecisionButtons: function() {
        $("div.app-meet-button a.yes").click(function(){return $Search.makeDecision(1);});
        $("div.app-meet-button a.maybe").click(function(){return $Search.makeDecision(0);});
        $("div.app-meet-button a.no").click(function(){return $Search.makeDecision(-1);});

        $("a.larr").click(function() {
            $Search.listPreviousPhoto();
            return false;
        });

        $("a.rarr").click(function() {
            $Search.listNextPhoto();
            return false;
        });

        $("div.message-help a#getmore").click(function() {
            var $extra = {service: {id: 2}};
            mamba.method('pay', 1, $.toJSON($extra));
            location.href = $Routing.getPath("billing");
        });
    },

    /**
     * Отправляет голосование и показывает следующую фотку
     *
     * @request decision.set
     */
    makeDecision: function($decision) {
        if ($Search.$storage['locked'] || !$Search.$storage['currentQueueElement']) {
            return;
        }

        if ($decision == 0) {
            $("div.app-meet-button > a.app-menu2").addClass("app-menu2-active");
        } else if ($decision == 1) {
            $("div.app-meet-button > a.app-menu1").addClass("app-menu1-active");
        } else if ($decision == -1) {
            $("div.app-meet-button > a.app-menu3").addClass("app-menu3-active");
        }

        $Search.$storage['locked'] = true;

        $Tools.ajaxPost('decision.set', { user_id: $Search.$storage['currentQueueElement']['info']['id'], decision: $decision }, function($data) {
            if ($data.status == 0 && $data.message == "") {
                if ($data.data['mutual']) {
                    $Layers.showMutualLayer();
                }

                if ($data.data['popularity']) {
                    $Config.$storage['webuser']['popularity'] = $data.data['popularity'];

                    var
                        $energy = $data.data['popularity']['energy'],
                        $next = $data.data['popularity']['next'],
                        $prev = $data.data['popularity']['prev'],
                        $level = $data.data['popularity']['level'],
                        $levelUp = $data.data['popularity']['level_up']
                    ;

                    $(".app-meet-button div.item-popularity div.bar div.level-background").attr('class', 'level-background lbc' + (parseInt(($energy - $prev)*100/($next - $prev)/25) + 1));
                    $(".app-meet-button div.item-popularity div.bar div.level").attr('class', 'level l' + $level);
                    $(".app-meet-button div.item-popularity div.bar div.speedo").css('width', parseInt(($energy - $prev)*100/($next - $prev)*0.99)+'px');

                    if ($levelUp) {
                        $Layers.showLevelAchievementLayer();
                    }
                }

                if ($data.data['is_contact']) {
                    var $texts = [
                        "Привет! Я отметил" + (($Config.get('webuser')['anketa']['info']['gender'] == 'M') ? '' : 'а') + " тебя в «Выбираторе». У тебя там столько оценок, класс!",
                        "Я видел" + (($Config.get('webuser')['anketa']['info']['gender'] == 'M') ? '' : 'а') + " твою анкету в «Выбираторе» и она мне понравилась ;)",
                        "Ух ты! Я наш" +  (($Config.get('webuser')['anketa']['info']['gender'] == 'M') ? 'ел' : 'ла')+ " тебя в «Выбираторе»!",
                        "Вау! Заходи скорее в «Выбиратор», я отправил" + (($Config.get('webuser')['anketa']['info']['gender'] == 'M') ? '' : 'а') + " тебе сюрприз!",
                        "У тебя классная анкета в «Выбираторе»!"
                    ];

                    var text = 'Привет! Я отметил' +  (($Config.get('webuser')['anketa']['info']['gender'] == 'M') ? '' : 'а') + ' тебя в приложении «Выбиратор», перейди по ссылке, чтобы посмотреть :)';
                    text = $texts[$Tools.rand(0, $texts.length - 1)];

                    //var ids = $Search.$storage['currentQueueElement']['info']['id'];
                    var ids = $data.data['spam_queue'];
                    mamba.method('message', text, '', ids);
                    $Config.set('message-text', text);
                    $Config.set('message-ids', ids);

                } else if ($data.data['repeat_warning'] == -1) {
                    $Layers.showRepeatableNoLayer();
                } else if ($data.data['repeat_warning'] == 0) {
                    $Layers.showRepeatableMaybeLayer();
                } else if ($data.data['repeat_warning'] == 1) {
                    $Layers.showRepeatableYesLayer();
                }

                $data = $data.data;
                if ($data.hasOwnProperty('counters')) {
                    if ($data['counters']['mychoice'] > 0 ) {
                        $('li.item-mychoice a i').eq(0).text($data['counters']['mychoice']);
                    }

                    if ($data['counters']['visitors'] > 0 ) {
                        $('li.item-visitors a i').eq(1).text($data['counters']['visitors']);
                    }

                    if ($data['counters']['visitors_unread'] > 0 ) {
                        $('li.item-visitors a i.plus b').eq(0).text('+' + $data['counters']['visitors_unread']);
                    } else {
                        $('li.item-visitors a i.plus b').eq(0).text('');
                    }

                    if ($data['counters']['mutual_unread'] > 0) {
                        $('li.item-mutual a i').eq(0).text('+' + $data['counters']['mutual_unread']);
                    } else if ($data['counters']['mutual'] > 0) {
                        $('li.item-mutual a i').eq(0).text($data['counters']['mutual']);
                    }

                    /*if ($data['counters']['messages_unread'] > 0) {
                        $('li.item-messages a i.plus b').text('+' + $data['counters']['messages_unread']);
                    } else {
                        $('li.item-messages a i.plus b').text('');
                    }*/

                    if ($data['counters']['events_unread'] > 0) {
                        $('li.item-profile a i.plus b').text('+' + $data['counters']['events_unread']);
                    } else {
                        $('li.item-profile a i.plus b').text('');
                    }
                }

                $Search.showNextPhoto();
                $Search.$storage['locked'] = false;

                $("div.app-meet-button > a.app-menu1").removeClass("app-menu1-active");
                $("div.app-meet-button > a.app-menu2").removeClass("app-menu2-active");
                $("div.app-meet-button > a.app-menu3").removeClass("app-menu3-active");
            } else {
                $status = $data.status;
                $message = $data.message;

                //$Search.showNextPhoto();
                $Search.$storage['locked'] = false;
                $Search.loadQueue(function(){
                    return $Search.unlockUI();
                });
                $Search.lockUI();
            }
        }, function() {
            top.location.href = $Config.get('platform')['partner_url'] + 'app_platform/?action=view&app_id=' + $Config.get('platform')['app_id'];
        });
    },

    /**
     * Показывает следующую фотографию из очереди
     *
     * @queue next
     */
    showNextPhoto: function() {

        var $activeId = $Config.get('active_id');
        if ($activeId) {
            var $__storage__ = [];
            for (var i=0;i<$Queue.$storage.length;i++) {
                if ($Queue.$storage[i].info.id == $activeId) {
                    $__storage__.push($Queue.$storage[i]);
                } else {
                    $__storage__.unshift($Queue.$storage[i]);
                }
            }
            $Queue.$storage = $__storage__;

            $Config.set('active_id', false)
        }

        var $currentQueueElement;

        if (this.$storage['currentQueueElement'] = $currentQueueElement = $Queue.get()) {
            this.$storage['currentPhotoNumber'] = 0;
            this.rebuildThumbsPanel();

            $("div.app-image-member div.name-container div.content a").html($currentQueueElement['info']['name']);

            if ($currentQueueElement['info']['age']) {
                $("div.app-image-member div.name-container div.content span").html($currentQueueElement['info']['age']);
            } else {
                $("div.app-image-member div.name-container div.content span").html("");
            }

            if ($currentQueueElement['info']['gender'] == 'F') {
                $("div.app-image-member div.name-container div.content i").removeClass('male');
                $("div.app-image-member div.name-container div.content i").addClass('female');
            } else {
                $("div.app-image-member div.name-container div.content i").removeClass('female');
                $("div.app-image-member div.name-container div.content i").addClass('male');
            }

            $("div.app-meet-button").fadeTo('normal', 1);
            $("div.app-lenta-img").show();
            $("div.app-image-member div.name-container").show();
            $("p.app-see-block").fadeTo('normal', 1);

            $("div.app-image-member img.app-image").attr({'src': $currentQueueElement['photos'][0]['huge_photo_url']}).show();
            window.clearTimeout(window.$loadingTimeout);
            window.$loadingTimeout = window.setTimeout(function(){
                $("div.app-image-member img.app-image").fadeTo('slow', 0.75);
            }, 750);

        } else {
            this.loadQueue(function(){
                return $Search.unlockUI();
            });
            return this.lockUI();
        }
    },

    /**
     * Перестроить панель тумб
     *
     * @rebuild thumbs
     */
    rebuildThumbsPanel: function() {
        var
            $currentQueueElement = this.$storage['currentQueueElement'],
            $photosCount = $currentQueueElement['photos'].length
        ;

        if ($currentQueueElement['photos'].length > 1) {

            var
                $html = "",
                $currentPhotoNumber = this.$storage['currentPhotoNumber'],
                $photosPerPage = 6
            ;

            if ($currentQueueElement['photos'].length-1 < $photosPerPage) {
                $("a.rarr").hide();
                $("a.larr").hide();
            } else {
                $("a.rarr").show();
                $("a.larr").show();
            }

            var $withOffset = ($currentPhotoNumber >= ($photosCount > $photosPerPage ? $photosPerPage: $photosCount));

            var $from = ($withOffset ? ($currentPhotoNumber + 1 - ($photosCount > $photosPerPage ? $photosPerPage: $photosCount)) : 0);
            if ($from + $photosPerPage >= $photosCount) {
                var $to = $photosCount;
            } else {
                var $to = $from + $photosPerPage;
            }

            for (var $i = $from;$i<$to;$i++) {
                $html += "<a class='" + ($i == this.$storage['currentPhotoNumber'] ? 'select' : '') + "' href=\"#\" pid='" + $i + "' style=\"background-image: url('" +  $currentQueueElement['photos'][$i]['small_photo_url'] + "')\"></a>";
            }

            $("div#thumbs").html($html);

            $("div#thumbs a").click(function() {
                $("div.app-image-member img.app-image").attr('src', $currentQueueElement['photos'][$Search.$storage['currentPhotoNumber'] = $(this).attr('pid')]['huge_photo_url']);
                window.clearTimeout(window.$loadingTimeout);
                window.$loadingTimeout = window.setTimeout(function(){
                    $("div.app-image-member img.app-image").fadeTo('slow', 0.75);
                }, 750);

                $('div.app-lenta-img a').removeClass('select');
                $(this).addClass('select');
                return false;
            });

            $("div.app-lenta").show();
        } else {
            $("div.app-lenta").hide();
        }
    },

    /**
     * Показывает следующую фотку по списку минифоток
     *
     * @shows next pic
     */
    listNextPhoto: function() {
        var
            $currentId = parseInt(this.$storage['currentPhotoNumber']),
            $size      = this.$storage['currentQueueElement'].photos.length
        ;

        if ($size < 2) {
            return;
        }

        var $nextId;

        if (($nextId = $currentId + 1) >= $size) {
            $nextId = 0;
        }

        $("div.app-image-member img.app-image").attr('src', this.$storage['currentQueueElement']['photos'][this.$storage['currentPhotoNumber'] = $nextId]['huge_photo_url']);
        window.clearTimeout(window.$loadingTimeout);
        window.$loadingTimeout = window.setTimeout(function(){
            $("div.app-image-member img.app-image").fadeTo('slow', 0.75);
        }, 750);

        this.rebuildThumbsPanel();
    },

    /**
     * Показывает предыдущую фотку по списку минифоток
     *
     * @shows prev pic
     */
    listPreviousPhoto: function() {
        var
            $currentId = parseInt(this.$storage['currentPhotoNumber']),
            $size      = this.$storage['currentQueueElement'].photos.length
        ;

        if ($size < 2) {
            return;
        }

        var $nextId;

        if (($nextId = $currentId - 1) < 0) {
            $nextId = this.$storage['currentQueueElement']['photos'].length - 1;
        }

        $("div.app-image-member img.app-image").attr('src', this.$storage['currentQueueElement']['photos'][this.$storage['currentPhotoNumber'] = $nextId]['huge_photo_url']);
        window.clearTimeout(window.$loadingTimeout);
        window.$loadingTimeout = window.setTimeout(function(){
            $("div.app-image-member img.app-image").fadeTo('slow', 0.75);
        }, 750);

        this.rebuildThumbsPanel();
    },

    /**
     * Запуск страницы
     *
     * @run page
     */
    run: function() {

        /**
         * Работа с очередью показа
         *
         * @author shpizel
         */
        if (!$Queue.qsize()) {
            this.loadQueue(function(){
                return $Search.unlockUI();
            });
            return this.lockUI();
        } else {
            this.showNextPhoto();
        }
    },

    /**
     * Загрузчик текущей очереди
     *
     * @param $callback
     */
    loadQueue: function($callback) {
        $Tools.ajaxPost('queue.get', {}, function($data) {
            if (typeof $data == 'object' && $data.status == 0 && $data.message == "") {
                for (var $i=0;$i<$data.data.length;$i++) {
                    $Queue.put($data.data[$i]);
                }

                $callback();
            } else {
                $status = $data.status;
                $message = $data.message;

                if ($status == 1) {
                    top.location.href = $Config.get('platform')['partner_url'] + 'app_platform/?action=view&app_id=' + $Config.get('platform')['app_id'];
                } else if ($status == 2) {
                    window.setTimeout(function() {
                        $Search.loadQueue($callback);
                    }, 1500);
                }
            }
        }, function() {
            top.location.href = $Config.get('platform')['partner_url'] + 'app_platform/?action=view&app_id=' + $Config.get('platform')['app_id'];
        });
    }
}