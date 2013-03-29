/**
 * Profile photos layer
 *
 * @author shpizel
 */

$Layers.$ProfilePhotosLayer = {

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
        $("div.app-layer").css('width', '655px');
        $("div.layer-profile-photos").show();
    },

    onClose: function() {
        $("div.app-layer").css('width', '550px');
    }
};