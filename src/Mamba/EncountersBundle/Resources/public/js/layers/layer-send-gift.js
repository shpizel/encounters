/**
 * Send gifts layer client scripts
 *
 * @author shpizel
 */

$Layers.$SendGiftLayer = {

    /**
     * Init UI
     *
     * @init UI
     */
    initUI: function() {
        $("div.layer-send-gift .list-present_item").click(function() {
            $("div.layer-send-gift .list-present_item").removeClass("list-present_item-selected");
            $(this).addClass("list-present_item-selected");
        });
    },

    /**
     * Shows layer
     *
     * @show layer
     */
    showLayer: function($data) {
        $("div.layer-send-gift").show();
    },

    /**
     * on close trigger
     *
     */
    onClose: function() {

    }
};