/**
 * Repeatable yes layer client scripts
 *
 * @author shpizel
 */

$Layers.$RepeatableYesLayer = {

    /**
     * Init UI
     *
     * @init UI
     */
    initUI: function() {

    },

    /**
     * Shows layer
     *
     * @show layer
     */
    showLayer: function($data) {
        $("div.layer-repeatable-yes .info-block span").text($Config.get('webuser')['anketa']['info']['name']);
        $("div.layer-repeatable-yes").show();
    }
};