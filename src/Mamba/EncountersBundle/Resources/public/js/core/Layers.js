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
        $("div.app-layer a.close").click(function() {
            $(this).parent().hide();
        });
    },

    showLayerAnswerMaybe: function() {
        $("div.layer-maybe").show();
        this.renderLayerPosition();
        this.showLayer();
    },

    /**
     * Показывает обрамление слоя
     *
     * @shows overflow
     */
    showLayer: function() {
        $("div.app-layer").show();
    },

    /**
     * Настраивает позицию слоя
     *
     * @render layer position
     */
    renderLayerPosition: function() {

    }
}