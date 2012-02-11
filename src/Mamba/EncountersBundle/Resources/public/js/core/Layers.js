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
        $("body").append("<div class='overflow'></div>");

        $("div.app-layer a.close").click(function() {
            $("div#overflow").hide();
            $("div.app-layer").hide();
        });
    },

    showLayerAnswerMaybe: function() {
        $("div.layer-maybe").show();
        this.showLayer();
    },

    /**
     * Показывает обрамление слоя
     *
     * @shows overflow
     */
    showLayer: function() {
        $("div.app-layer").show();
        $("div#overflow").show().click(function() {
            $(this).hide();
            $("div.app-layer").hide();
        });
    }
}