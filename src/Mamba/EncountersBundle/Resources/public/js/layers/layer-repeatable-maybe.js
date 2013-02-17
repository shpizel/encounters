/**
 * Repeatable maybe layer client scripts
 *
 * @author shpizel
 */

$Layers.$RepeatableMaybeLayer = {

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
        $("div.layer-repeatable-maybe .info-block span").text($Config.get('webuser')['anketa']['info']['name']);
        $("div.layer-repeatable-maybe").show();
    }
};