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


        var $layersList = [
            this.$AccountLayer,
            this.$AnswerMaybeLayer,
            this.$AnswerNoLayer,
            this.$AnswerYesLayer,
            this.$BatteryLayer,
            this.$EnergyLayer,
            this.$LevelAchievementLayer,
            this.$LevelLayer,
            this.$MutualLayer,
            this.$AnswerNotSeeYetLayer,
            this.$PhotolinePurchaseLayer,
            this.$RepeatableMaybeLayer,
            this.$RepeatableNoLayer,
            this.$RepeatableYesLayer,
            this.$UserInfoLayer
        ];

        for (var i=0;i<$layersList.length;i++) {
            $layersList[i].initUI();
        }
    },

    openMessengerWindowFunction: function() {
        var e = screen.availHeight<800 ? screen.availHeight - 150 : 620;
        try {
            $Config.get('messenger.popup') && $Config.get('messenger.popup').close();
            $Config.set('messenger.popup', window.open($Config.get('platform')['partner_url'] + 'my/message.phtml?oid=' +  $(this).attr('user_id') ,"Messenger","width=750,height=" + e + ",resizable=1,scrollbars=1"));
            $Config.get('messenger.popup').focus();
        } catch (e) {}

        return false;
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
     * Показывает лаер
     *
     * @shows overflow
     */
    showLayer: function($Layer, $data) {
        this.hideInners();

        $Layer.showLayer($data);

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
    },

    /**
     * Показывает лаер счета
     *
     * @shows layer
     */
    showAccountLayer: function($data) {
        this.showLayer(this.$AccountLayer, $data);
    },

    /**
     * Он(а) ответил(а) может быть
     *
     * @shows layer
     */
    showAnswerMaybeLayer: function($data) {
        this.showLayer(this.$AnswerMaybeLayer, $data);
    },

    /**
     * Он(а) ответил(а) нет
     *
     * @shows layer
     */
    showAnswerNoLayer: function($data) {
        this.showLayer(this.$AnswerNoLayer, $data);
    },

    /**
     * Он(а) ответил(а) да
     *
     * @shows layer
     */
    showAnswerYesLayer: function($data) {
        this.showLayer(this.$AnswerYesLayer, $data);
    },

    /**
     * Показывает лаер батарейки при клике на батарейку
     *
     * @shows layer
     */
    showBatteryLayer: function($data) {
        this.showLayer(this.$BatteryLayer, $data);
    },

    /**
     * Не хватает энергии
     *
     * @shows layer
     */
    showEnergyLayer: function($data) {
        this.showLayer(this.$EnergyLayer, $data);
    },

    /**
     * Показывает лаер достижения уровня
     *
     * @shows layer
     */
    showLevelAchievementLayer: function($data) {
        this.showLayer(this.$LevelAchievementLayer, $data);
    },

    /**
     * Показывает лаер уровня
     *
     * @shows layer
     */
    showLevelLayer: function($data) {
        this.showLayer(this.$LevelLayer, $data);
    },

    /**
     * Показывает лаер совпадения
     *
     * @shows layer
     */
    showMutualLayer: function($data) {
        this.showLayer(this.$MutualLayer, $data);
    },

    /**
     * Он(а) еще не видел(а) ваши фотки
     *
     * @shows layer
     */
    showAnswerNotSeeYetLayer: function($data) {
        this.showLayer(this.$AnswerNotSeeYetLayer, $data);
    },

    /**
     * Показывает лаер заказа мордоленты
     *
     * @shows layer
     */
    showPhotolinePurchaseLayer: function($data) {
        this.showLayer(this.$PhotolinePurchaseLayer, $data);
    },

    /**
     * Показывает лаер слишком частого ВОЗМОЖНО
     *
     * @shows layer
     */
    showRepeatableMaybeLayer: function($data) {
        this.showLayer(this.$RepeatableMaybeLayer, $data);
    },

    /**
     * Показывает лаер слишком частого НЕТ
     *
     * @shows layer
     */
    showRepeatableNoLayer: function($data) {
        this.showLayer(this.$RepeatableNoLayer, $data);
    },

    /**
     * Показывает лаер слишком частого ДА
     *
     * @shows layer
     */
    showRepeatableYesLayer: function($data) {
        this.showLayer(this.$RepeatableYesLayer, $data);
    },

    /**
     * Показывает лаер пользовательского инфо при клике на юзера
     *
     * @shows layer
     */
    showUserInfoLayer: function($data) {
        this.showLayer(this.$UserInfoLayer, $data);
    }
}