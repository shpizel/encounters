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
    showAnswerMaybeLayer: function() {
        this.hideInners();
        var currentQueueElement = $Search.$storage['currentQueueElement'];
        $("div.layer-maybe div.photo img").attr('src', currentQueueElement['info']['medium_photo_url']);
        $("div.layer-maybe div.content-center a").attr('href', $Config.get('platform').partner_url + "anketa.phtml?oid=" + currentQueueElement['info']['id']).text(currentQueueElement['info']['name']);
        $("div.layer-maybe div.center a.see").attr('href', $Config.get('platform').partner_url + "anketa.phtml?oid=" + currentQueueElement['info']['id']);

        $("div.layer-maybe").show();
        this.showLayer();
    },

    /**
     * Он(а) ответил(а) да
     *
     * @shows layer
     */
    showAnswerYesLayer: function() {
        this.hideInners();
        var currentQueueElement = $Search.$storage['currentQueueElement'];
        $("div.layer-yes div.photo img").attr('src', currentQueueElement['info']['medium_photo_url']);
        $("div.layer-yes div.content-center a").attr('href', $Config.get('platform').partner_url + "anketa.phtml?oid=" + currentQueueElement['info']['id']).text(currentQueueElement['info']['name']);
        $("div.layer-yes div.center a.see").attr('href', $Config.get('platform').partner_url + "anketa.phtml?oid=" + currentQueueElement['info']['id']);

        $("div.layer-yes").show();
        this.showLayer();
    },

    /**
     * Он(а) ответил(а) нет
     *
     * @shows layer
     */
    showAnswerNoLayer: function() {
        this.hideInners();
        var currentQueueElement = $Search.$storage['currentQueueElement'];
        $("div.layer-no div.photo img").attr('src', currentQueueElement['info']['medium_photo_url']);
        $("div.layer-no div.content-center span.name").text(currentQueueElement['info']['name']);

        $("div.layer-no").show();
        this.showLayer();
    },

    /**
     * Не хватает энергии
     *
     * @shows layer
     */
    showEnergyLayer: function() {
        this.hideInners();
        var currentQueueElement = $Search.$storage['currentQueueElement'];
        $("div.layer-energy div.info-block img").attr('src', currentQueueElement['info']['small_photo_url']);
        $("div.layer-energy span.name").text(currentQueueElement['info']['name']);
        $("div.layer-energy form p a").click(function() {
            mamba.method('openPaymentLayer', $Config.get('platform').app_id, 1);
        });
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
        $("div.layer-pop-up form p a").click(function() {
            mamba.method('openPaymentLayer', $Config.get('platform').app_id, 1);
        });
        this.showLayer();
    },

    /**
     * Он(а) еще не видел(а) ваши фотки
     *
     * @shows layer
     */
    showAnswerNotSeeYetLayer: function() {
        this.hideInners();
        var currentQueueElement = $Search.$storage['currentQueueElement'];
        $("div.layer-not-see-yet div.photo img").attr('src', currentQueueElement['info']['medium_photo_url']);
        $("div.layer-not-see-yet div.center a").click(function() {
            mamba.method('openPaymentLayer', $Config.get('platform').app_id, 1);
        });
        $("div.layer-not-see-yet").show();
        this.showLayer();
    },

    /**
     * Он(а) еще не видел(а) ваши фотки
     *
     * @shows layer
     */
    showMutualLayer: function() {
        this.hideInners();
        var currentQueueElement = $Search.$storage['currentQueueElement'];

        $("div.layer-mutual div.photo div.current").css('background', "url('" + currentQueueElement['info']['medium_photo_url'] + "')");
        console.log($Config.get('webuser'));
        $("div.layer-mutual div.photo div.web").css('background', "url('" + (($Config.get('webuser')['anketa']['info'].hasOwnProperty('medium_photo_url') && $Config.get('webuser')['anketa']['info']['medium_photo_url']) ? $Config.get('webuser')['anketa']['info']['medium_photo_url'] : '/bundles/encounters/images/photo_big_na.gif') + "')");
        $("div.layer-mutual div.content-center a").attr('href', $Config.get('platform').partner_url + "anketa.phtml?oid=" + currentQueueElement['info']['id']).text(currentQueueElement['info']['name']);
        $("div.layer-mutual div.center a.see").attr('href', $Config.get('platform').partner_url + "anketa.phtml?oid=" + currentQueueElement['info']['id']);
        $("div.layer-mutual").show();
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