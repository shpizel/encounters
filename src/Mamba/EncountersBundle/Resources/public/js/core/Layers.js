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
            $.post($Routing.getPath('service.add'), {service: {id: 1}}, function($data) {
                if ($data.status == 0 && $data.message == "") {
                    mamba.method('openPaymentLayer', $Config.get('platform').app_id, 1);
                    location.href = $Routing.getPath("billing");
                }
            });

            return false;
        });

        $("div.layer-battery form p a.ui-btn").click(function() {
            $.post($Routing.getPath('service.add'), {service: {id: 1}}, function($data) {
                if ($data.status == 0 && $data.message == "") {
                    mamba.method('openPaymentLayer', $Config.get('platform').app_id, 1);
                    location.href = $Routing.getPath("billing");
                }
            });

            return false;
        });

        $("div.layer-not-see-yet div.center a").click(function() {
            $.post($Routing.getPath('service.add'), {service: {id: 2, user_id: ($Search.$storage.hasOwnProperty('currentQueueElement') ? $Search.$storage['currentQueueElement']['info']['id'] : $Config.get('current_user_id'))}}, function($data) {
                if ($data.status == 0 && $data.message == "") {
                    mamba.method('openPaymentLayer', $Config.get('platform').app_id, 1);
                    location.href = $Routing.getPath("billing");
                }
            });

            return false;
        });

        $("div.layer-pop-up form p a").click(function() {
            $.post($Routing.getPath('service.add'), {service: {id: 3}}, function($data) {
                if ($data.status == 0 && $data.message == "") {
                    mamba.method('openPaymentLayer', $Config.get('platform').app_id, 1);
                    location.href = $Routing.getPath("billing");
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
     * Показывает обрамление слоя
     *
     * @shows overflow
     */
    showLayer: function() {
        $("div.app-layer").show();
        $("div#overflow").show();
    }
}