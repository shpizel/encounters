/**
 * Layers
 *
 * @author shpizel
 */
$Layers = {

    /**
     * Opened layers
     *
     * @var array
     */
    $openedLayers: [],

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

            for (var $i=0;$i<$Layers.$openedLayers.length;$i++) {
                $Layer = $Layers.$openedLayers[$i];
                console.log($Layer);

                if ($Layer.hasOwnProperty("onClose")) {
                    $Layer.onClose();
                }
            }

            $Layers.$openedLayers = [];

            return false;
        });

        $("div.app-layer a.close").click(function() {
            $("div#overflow").hide();
            $("div.app-layer").hide();

            for (var $i=0;$i<$Layers.$openedLayers.length;$i++) {
                $Layer = $Layers.$openedLayers[$i];
                console.log($Layer);

                if ($Layer.hasOwnProperty("onClose")) {
                    $Layer.onClose();
                }
            }

            $Layers.$openedLayers = [];

            return false;
        });

        /**
         * Layer list for initUI
         *
         * @type {Array}
         */
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
            this.$UserInfoLayer,
            this.$SendGiftLayer,
            this.$ProfilePhotosLayer
        ];

        for (var i=0;i<$layersList.length;i++) {
            $layersList[i].initUI();
        }
    },

    openMessengerWindowFunction: function($userId) {
        var e = screen.availHeight<800 ? screen.availHeight - 150 : 620;
        try {
            $Config.get('messenger.popup') && $Config.get('messenger.popup').close();

            if (!$userId) {
                $userId = $(this).attr('user_id');
            }

            $Config.set('messenger.popup', window.open($Config.get('platform')['partner_url'] + 'my/message.phtml?oid=' +  $userId ,"Messenger","width=750,height=" + e + ",resizable=1,scrollbars=1"));
            //$Config.set('messenger.popup', window.open('/messenger?id=' +  $userId, "Messenger", "width=860,height=" + e + ",resizable=1,scrollbars=1"));
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
        this.showLayer(this.registerOpenedLayer(this.$AccountLayer), $data);
    },

    /**
     * Он(а) ответил(а) может быть
     *
     * @shows layer
     */
    showAnswerMaybeLayer: function($data) {
        this.showLayer(this.registerOpenedLayer(this.$AnswerMaybeLayer), $data);
    },

    /**
     * Он(а) ответил(а) нет
     *
     * @shows layer
     */
    showAnswerNoLayer: function($data) {
        this.showLayer(this.registerOpenedLayer(this.$AnswerNoLayer), $data);
    },

    /**
     * Он(а) ответил(а) да
     *
     * @shows layer
     */
    showAnswerYesLayer: function($data) {
        this.showLayer(this.registerOpenedLayer(this.$AnswerYesLayer), $data);
    },

    /**
     * Показывает лаер батарейки при клике на батарейку
     *
     * @shows layer
     */
    showBatteryLayer: function($data) {
        this.showLayer(this.registerOpenedLayer(this.$BatteryLayer), $data);
    },

    /**
     * Не хватает энергии
     *
     * @shows layer
     */
    showEnergyLayer: function($data) {
        this.showLayer(this.registerOpenedLayer(this.$EnergyLayer), $data);
    },

    /**
     * Показывает лаер достижения уровня
     *
     * @shows layer
     */
    showLevelAchievementLayer: function($data) {
        this.showLayer(this.registerOpenedLayer(this.$LevelAchievementLayer), $data);
    },

    /**
     * Показывает лаер уровня
     *
     * @shows layer
     */
    showLevelLayer: function($data) {
        this.showLayer(this.registerOpenedLayer(this.$LevelLayer), $data);
    },

    /**
     * Показывает лаер совпадения
     *
     * @shows layer
     */
    showMutualLayer: function($data) {
        this.showLayer(this.registerOpenedLayer(this.$MutualLayer), $data);
    },

    /**
     * Он(а) еще не видел(а) ваши фотки
     *
     * @shows layer
     */
    showAnswerNotSeeYetLayer: function($data) {
        this.showLayer(this.registerOpenedLayer(this.$AnswerNotSeeYetLayer), $data);
    },

    /**
     * Показывает лаер заказа мордоленты
     *
     * @shows layer
     */
    showPhotolinePurchaseLayer: function($data) {
        this.showLayer(this.registerOpenedLayer(this.$PhotolinePurchaseLayer), $data);
    },

    /**
     * Показывает лаер слишком частого ВОЗМОЖНО
     *
     * @shows layer
     */
    showRepeatableMaybeLayer: function($data) {
        this.showLayer(this.registerOpenedLayer(this.$RepeatableMaybeLayer), $data);
    },

    /**
     * Показывает лаер слишком частого НЕТ
     *
     * @shows layer
     */
    showRepeatableNoLayer: function($data) {
        this.showLayer(this.registerOpenedLayer(this.$RepeatableNoLayer), $data);
    },

    /**
     * Показывает лаер слишком частого ДА
     *
     * @shows layer
     */
    showRepeatableYesLayer: function($data) {
        this.showLayer(this.registerOpenedLayer(this.$RepeatableYesLayer), $data);
    },

    /**
     * Показывает лаер пользовательского инфо при клике на юзера
     *
     * @shows layer
     */
    showUserInfoLayer: function($data) {
        this.showLayer(this.registerOpenedLayer(this.$UserInfoLayer), $data);
    },

    /**
     * Показывает лаер дарения подарка
     *
     * @shows layer
     */
    showSendGiftLayer: function($data) {
        this.showLayer(this.registerOpenedLayer(this.$SendGiftLayer), $data);
    },

    /**
     * Показывает лаер фотографий профиля
     *
     * @shows layer
     */
    showProfilePhotosLayer: function($data) {
        this.showLayer(this.registerOpenedLayer(this.$ProfilePhotosLayer), $data);
    },

    /**
     * Register opened layer
     *
     * @param $Layer
     * @returns {*}
     */
    registerOpenedLayer: function($Layer) {
        this.$openedLayers.push($Layer);
        return $Layer;
    }
}