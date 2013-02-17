/**
 * Repeatable no layer client scripts
 *
 * @author shpizel
 */

$Layers.$RepeatableNoLayer = {

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
        $("div.layer-repeatable-no .info-block span").text($Config.get('webuser')['anketa']['info']['name']);
        $("div.layer-repeatable-no").show();
    }
};