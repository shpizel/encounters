/**
 * Layers
 *
 * @author shpizel
 */
$Layers = {

    /**
     * Init UI
     *
     * @init UI
     */
    initUI: function() {
        $("body").append("<div id='overflow'></div>");
        $("div#overflow").click(function() {
            $(this).hide();
            $("div.app-layer").hide();
        });

        $("div.app-layer a.close").click(function() {
            $("div#overflow").hide();
            $("div.app-layer").hide();

            return false;
        });

        $("div.layer-energy form p a").click(function() {
            var $extra = {service: {id: 1}};
            $.post($Routing.getPath('service.add'), $extra, function($data) {
                if ($data.status == 0 && $data.message == "") {
                    mamba.method('pay', 1, $.toJSON($extra));
                }
            });

            return false;
        });

        $("div.layer-battery form p a.ui-btn").click(function() {
            var $extra = {service: {id: 1}};
            $.post($Routing.getPath('service.add'), $extra, function($data) {
                if ($data.status == 0 && $data.message == "") {
                    mamba.method('pay', 1, $.toJSON($extra));
                }
            });

            return false;
        });

        $("div.layer-not-see-yet div.center a").click(function() {
            var $extra = {service: {id: 2, user_id: ($Search.$storage.hasOwnProperty('currentQueueElement') ? $Search.$storage['currentQueueElement']['info']['id'] : $Config.get('current_user_id'))}};
            $.post($Routing.getPath('service.add'), $extra, function($data) {
                if ($data.status == 0 && $data.message == "") {
                    mamba.method('pay', 1, $.toJSON($extra));
                }
            });

            return false;
        });

        $("div.layer-pop-up form p a").click(function() {
            var $extra = {service: {id: 3}};
            $.post($Routing.getPath('service.add'), $extra, function($data) {
                if ($data.status == 0 && $data.message == "") {
                    mamba.method('pay', 3, $.toJSON($extra));
                }
            });

            return false;
        });

        $("div.layer-invite form p a").click(function() {
            $("div#overflow").hide();
            $("div.app-layer").hide();
            mamba.method('message', 'Привет! Я отметил' +  (($Config.get('webuser')['anketa']['info']['gender'] == 'M') ? '' : 'а') + ' тебя в приложении «Выбиратор», перейди по ссылке, чтобы посмотреть :)', '', $(this).attr('ids'));
            return false;
        });

        $("div.layer-user-info a.ui-btn").click(function() {
            var e = screen.availHeight<800 ? screen.availHeight - 150 : 620;
            try {
                $Config.get('messenger.popup') && $Config.get('messenger.popup').close();
                $Config.set('messenger.popup', window.open($Config.get('platform')['partner_url'] + 'my/message.phtml?oid=' +  $(this).attr('user_id') ,"Messenger","width=750,height="+e+",resizable=1,scrollbars=1"));
                $Config.get('messenger.popup').focus();
            } catch (e) {}

            return false;
        });

        $("div.layer-level-achievement div._tell a.see").click(function() {
            if ($Config.get('webuser')['contacts']['not_app_users'].length) {
                mamba.method('message', "Привет! Установи приложение «Выбиратор», в нем очень удобно смотреть анкеты, плюс — твои фотографии тоже очень быстро получат множество просмотров и оценок ;-)", '', $Config.get('webuser')['contacts']['not_app_users']);
            } else {
                mamba.method('message', "Привет! Установи приложение «Выбиратор», в нем очень удобно смотреть анкеты, плюс — твои фотографии тоже очень быстро получат множество просмотров и оценок ;-)", '', $Config.get('webuser')['contacts']['all']);
            }
            $("div#overflow").hide();
            $("div.app-layer").hide();

            return false;
        });

        $("div.layer-level div._tell a.see").click(function() {
            if ($Config.get('webuser')['contacts']['not_app_users'].length) {
                mamba.method('message', "Привет! Установи приложение «Выбиратор», в нем очень удобно смотреть анкеты, плюс — твои фотографии тоже очень быстро получат множество просмотров и оценок ;-)", '', $Config.get('webuser')['contacts']['not_app_users']);
            } else {
                mamba.method('message', "Привет! Установи приложение «Выбиратор», в нем очень удобно смотреть анкеты, плюс — твои фотографии тоже очень быстро получат множество просмотров и оценок ;-)", '', $Config.get('webuser')['contacts']['all']);
            }

            $("div#overflow").hide();
            $("div.app-layer").hide();

            return false;
        });

        $("div.layer-level p a.ui-btn").click(function() {
            var $cost = $(this).attr('cost'), $level = $(this).attr('level');
            var $extra = {service: {id: 4, level: $level}};
            $.post($Routing.getPath('service.add'), $extra, function($data) {
                if ($data.status == 0 && $data.message == "") {
                    mamba.method('pay', $cost, $.toJSON($extra));
                }
            });

            return false;
        });
    },

    /**
     * Скрывает кишки лаеров
     *
     * @hides layers inners
     */
    hideInners: function() {
        $("div.app-layer-wrap > div").each(function() {
            $(this).hide();
        });
    },

    /**
     * Он(а) ответил(а) может быть
     *
     * @shows layer
     */
    showAnswerMaybeLayer: function($data) {
        this.hideInners();
        if (!$data) {
            var currentQueueElement = $Search.$storage['currentQueueElement'];
            $("div.layer-maybe div.photo img").attr('src', currentQueueElement['info']['medium_photo_url']);
            $("div.layer-maybe div.content-center a").attr('href', $Config.get('platform').partner_url + "anketa.phtml?oid=" + currentQueueElement['info']['id']).text(currentQueueElement['info']['name']);
            $("div.layer-maybe div.center a.see").attr('href', $Config.get('platform').partner_url + "anketa.phtml?oid=" + currentQueueElement['info']['id']);
        } else {
            $("div.layer-maybe div.photo img").attr('src', $data['medium_photo_url']);
            $("div.layer-maybe div.content-center a").attr('href', $Config.get('platform').partner_url + "anketa.phtml?oid=" + $data['user_id']).text($data['name']);
            $("div.layer-maybe div.center a.see").attr('href', $Config.get('platform').partner_url + "anketa.phtml?oid=" + $data['user_id']);
        }

        $("div.layer-maybe").show();
        this.showLayer();
    },

    /**
     * Он(а) ответил(а) да
     *
     * @shows layer
     */
    showAnswerYesLayer: function($data) {
        this.hideInners();
        if (!$data) {
            var currentQueueElement = $Search.$storage['currentQueueElement'];
            $("div.layer-yes div.photo img").attr('src', currentQueueElement['info']['medium_photo_url']);
            $("div.layer-yes div.content-center a").attr('href', $Config.get('platform').partner_url + "anketa.phtml?oid=" + currentQueueElement['info']['id']).text(currentQueueElement['info']['name']);
            $("div.layer-yes div.center a.see").attr('href', $Config.get('platform').partner_url + "anketa.phtml?oid=" + currentQueueElement['info']['id']);
        } else {
            $("div.layer-yes div.photo img").attr('src', $data['medium_photo_url']);
            $("div.layer-yes div.content-center a").attr('href', $Config.get('platform').partner_url + "anketa.phtml?oid=" + $data['user_id']).text($data['name']);
            $("div.layer-yes div.center a.see").attr('href', $Config.get('platform').partner_url + "anketa.phtml?oid=" + $data['user_id']);
        }

        $("div.layer-yes").show();
        this.showLayer();
    },

    /**
     * Он(а) ответил(а) нет
     *
     * @shows layer
     */
    showAnswerNoLayer: function($data) {
        this.hideInners();
        if (!$data) {
            var currentQueueElement = $Search.$storage['currentQueueElement'];
            $("div.layer-no div.photo img").attr('src', currentQueueElement['info']['medium_photo_url']);
            $("div.layer-no div.content-center span.name").text(currentQueueElement['info']['name']);
        } else {
            $("div.layer-no div.photo img").attr('src', $data['medium_photo_url']);
            $("div.layer-no div.content-center span.name").text($data['name']);
        }

        $("div.layer-no").show();
        this.showLayer();
    },

    /**
     * Не хватает энергии
     *
     * @shows layer
     */
    showEnergyLayer: function($data) {
        this.hideInners();
        if (!$data) {
            var currentQueueElement = $Search.$storage['currentQueueElement'];
            $("div.layer-energy div.info-block img").attr('src', currentQueueElement['info']['small_photo_url']);
            $("div.layer-energy span.name").text(currentQueueElement['info']['name']);
        } else {
            $("div.layer-energy div.info-block img").attr('src', $data['small_photo_url']);
            $("div.layer-energy span.name").text($data['name']);
        }

        $("div.layer-energy").show();
        this.showLayer();
    },

    /**
     * Станьте популярнее
     *
     * @shows layer
     */
    showPopularityLayer: function() {
        this.hideInners();
        $("div.layer-pop-up").show();
        this.showLayer();
    },

    /**
     * Он(а) еще не видел(а) ваши фотки
     *
     * @shows layer
     */
    showAnswerNotSeeYetLayer: function($data) {
        this.hideInners();
        if (!$data) {
            var currentQueueElement = $Search.$storage['currentQueueElement'];
            $("div.layer-not-see-yet div.photo img").attr('src', currentQueueElement['info']['medium_photo_url']);
        } else {
            $Config.set('current_user_id', $data['user_id']);
            $("div.layer-not-see-yet div.photo img").attr('src', $data['medium_photo_url']);
        }
        $("div.layer-not-see-yet").show();
        this.showLayer();
    },

    /**
     * Он(а) еще не видел(а) ваши фотки
     *
     * @shows layer
     */
    showMutualLayer: function($data) {
        this.hideInners();
        if (!$data) {
            var currentQueueElement = $Search.$storage['currentQueueElement'];
            $("div.layer-mutual div.photo div.current").css('background', "url('" + currentQueueElement['info']['medium_photo_url'] + "')");
            $("div.layer-mutual div.content-center a").attr('href', $Config.get('platform').partner_url + "anketa.phtml?oid=" + currentQueueElement['info']['id']).text(currentQueueElement['info']['name']);
            $("div.layer-mutual div.center a.see").attr('href', $Config.get('platform').partner_url + "anketa.phtml?oid=" + currentQueueElement['info']['id']);
        } else {
            $("div.layer-mutual div.photo div.current").css('background', "url('" + $data['medium_photo_url'] + "')");
            $("div.layer-mutual div.content-center a").attr('href', $Config.get('platform').partner_url + "anketa.phtml?oid=" + $data['user_id']).text($data['name']);
            $("div.layer-mutual div.center a.see").attr('href', $Config.get('platform').partner_url + "anketa.phtml?oid=" + $data['user_id']);
        }

        $("div.layer-mutual div.photo div.web").css('background', "url('" + (($Config.get('webuser')['anketa']['info'].hasOwnProperty('medium_photo_url') && $Config.get('webuser')['anketa']['info']['medium_photo_url']) ? $Config.get('webuser')['anketa']['info']['medium_photo_url'] : '/bundles/encounters/images/photo_big_na.gif') + "')");
        $("div.layer-mutual").show();
        this.showLayer();
    },

    /**
     * Показывает лаер батарейки при клике на батарейку
     *
     * @shows layer
     */
    showBatteryLayer: function($data) {
        this.hideInners();
        var $charge = $Battery.getCharge();
        if ($charge == 0) {
            $("div.layer-battery div.battery-big").attr('class', 'battery-big empty');
            $("div.layer-battery .title").attr('class', 'title');
            $("div.layer-battery p.center a.ui-btn").html("Купить 100% энергии за 1<i class=\"coint\"></i>");
            $("div.layer-battery p.center a.close").hide();
            $("div.layer-battery p.center a.ui-btn").show();
        } else if ($charge >= 0 && $charge < 5 ) {
            $("div.layer-battery div.battery-big").attr('class', 'battery-big middle');
            $("div.layer-battery .title").attr('class', 'title');
            $("div.layer-battery p.center a.ui-btn").html("Пополнить до 100% за 1<i class=\"coint\"></i>");
            $("div.layer-battery p.center a.close").hide();
            $("div.layer-battery p.center a.ui-btn").show();
        } else {
            $("div.layer-battery div.battery-big").attr('class', 'battery-big full');
            $("div.layer-battery .title").attr('class', 'title charged');
            $("div.layer-battery p.center a.ui-btn").hide();
            $("div.layer-battery p.center a.close").show();
        }

        $("div.layer-battery").show();
        this.showLayer();
    },

    /**
     * Показывает лаер пользовательского инфо при клике на юзера
     *
     * @shows layer
     */
    showUserInfoLayer: function($data) {
        this.hideInners();

        var currentQueueElement = ($data) ? $data : $Search.$storage['currentQueueElement'];
        $("div.layer-user-info div.face img").attr('src', currentQueueElement['info']['medium_photo_url']);
        $("div.layer-user-info a.ui-btn").attr("user_id", currentQueueElement['info']['id']);
        $("div.layer-user-info div.info div.name a").attr('href', $Config.get('platform').partner_url + "anketa.phtml?oid=" + currentQueueElement['info']['id']).text(currentQueueElement['info']['name']);

        if (currentQueueElement['info']['gender'] == 'F') {
            $("div.layer-user-info div.info div.name i").removeClass('male');
            $("div.layer-user-info div.info div.name i").addClass('female');
        } else {
            $("div.layer-user-info div.info div.name i").removeClass('female');
            $("div.layer-user-info div.info div.name i").addClass('male');
        }

        $("div.layer-user-info div.info div.location").html(currentQueueElement['info']['location']['country'] + ", " + currentQueueElement['info']['location']['city']);
        $("div.layer-user-info div.info div.age").html((currentQueueElement['info']['age'] ? (currentQueueElement['info']['age'] + ', ' + currentQueueElement['info']['other']['sign']) : '&nbsp;'));
        $("div.layer-user-info div.info div.lookingfor span").html(currentQueueElement['info']['familiarity']['lookfor']);

        $("div.layer-user-info").show();
        this.showLayer();
    },

    /**
     * Показывает лаер приглашения людей извне приложения
     *
     * @shows layer
     */
    showInviteLayer: function($data) {
        this.hideInners();

        $("div.layer-invite div.pics").html('');
        var $item, $ids = [];
        while (true) {
            if ($item = $inviteQueue.get()) {
                $("div.layer-invite div.pics").append("<img src='" + $item['info']['small_photo_url'] + "'>");
                $ids.push($item['info']['id']);
            } else {
                break;
            }
        }

        $("div.layer-invite form p a").attr('ids', $ids.join(','));
        $("div.layer-invite").show();
        this.showLayer();
    },

    /**
     * Показывает лаер достижения уровня
     *
     * @shows layer
     */
    showLevelAchievementLayer: function($data) {
        this.hideInners();

        var $level = $Config.get('webuser')['popularity']['level'];
        $("div.layer-level-achievement div.level").attr("class", 'level l' + $level);

        /*if ($level % 4 == 0) {
            $("div.layer-level-achievement b.battery").html("+2 деления в подарок");

            $("div.layer-level-achievement p._close").hide();
            $("div.layer-level-achievement div._tell").show();
        } else {
            $("div.layer-level-achievement b.battery").html("+1 деление в подарок");

            $("div.layer-level-achievement p._close").show();
            $("div.layer-level-achievement div._tell").hide();
        }*/

        $("div.layer-level-achievement").show();
        this.showLayer();
    },

    /**
     * Показывает лаер уровня
     *
     * @shows layer
     */
    showLevelLayer: function($data) {
        this.hideInners();

        var $popularity = $Config.get('webuser')['popularity'];
        $("div.layer-level div.level").attr("class", 'level l' + $popularity['level']);
        $("div.layer-level div.current").attr("class", 'current l' + $popularity['level']);
        $("div.layer-level div.next").attr("class", 'next l' + ($popularity['level'] < 16 ? ($popularity['level'] + 1) : $popularity['level']));
        $("div.layer-level div.speedo").css('width', parseInt(($popularity['energy'] - $popularity['prev'])*100/($popularity['next'] - $popularity['prev'])*1.99)+'px');

        if ($popularity['level'] < 16 && $popularity['level'] >= 4) {
            $("div.layer-level p a.ui-btn").html("Перейти на " + ($popularity['level']+1) + "-й уровень за " + ($popularity['level'] + 1 - 4) +"<i class=\"coint\"></i>");
            $("div.layer-level p a.ui-btn").attr('cost',  $popularity['level'] + 1 - 4);
            $("div.layer-level p a.ui-btn").attr('level',  $popularity['level'] + 1);
            $("div.layer-level p._buy").show();
            $("div.layer-level div._tell").hide();
        } else {
            $("div.layer-level div._tell").show();
            $("div.layer-level p._buy").hide();
        }

        $("div.layer-level").show();
        this.showLayer();
    },

    /**
     * Показывает обрамление слоя
     *
     * @shows overflow
     */
    showLayer: function() {
        var $dimensions = $Config.get('dimensions');
        if ($dimensions) {
            var
                $applicationClientHeight = $dimensions.height,
                $wrapperHeight = $("#overflow").height() || $("#wrapper").height(),
                $layerHeight = $(".app-layer").height() + 40
            ;

            var $layerY = $applicationClientHeight / 2 - $layerHeight / 2 - $dimensions.offsetTop + $dimensions.scrollTop;
            if ($layerY < 100) {
                $layerY = 100;
            }

            $(".app-layer").css('top', $layerY + 'px');
        }

        $("div.app-layer").show();
        $("div#overflow").show();
    }
}