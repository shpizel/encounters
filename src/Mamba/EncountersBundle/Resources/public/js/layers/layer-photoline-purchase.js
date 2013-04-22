/**
 * Photoline purchase layer client scripts
 *
 * @author shpizel
 */

$Layers.$PhotolinePurchaseLayer = {

    /**
     * Init UI
     *
     * @init UI
     */
    initUI: function() {
        $("div.layer-photoline-purchase p a.ui-btn").click(function() {
            var $comment = $("div.layer-photoline-purchase textarea").val();
            if ($comment.length > 80) {
                $comment = $comment.substr(0, 79) + "…";
            }


            $Tools.ajaxPost('photoline.purchase', {'comment': $comment}, function($data) {
                if ($data.status == 0 && $data.message == "") {
                    $Account.setAccount($data.data['account']);

                    $("div#overflow").hide();
                    $("div.app-layer").hide();
                } else if ($data.status == 3) {
                    $Layers.showAccountLayer({'status': $data.status});
                }
            });

            return false;
        });
    },

    /**
     * Shows layer
     *
     * @show layer
     */
    showLayer: function($data) {
        $(".layer-photoline-purchase .title img").attr('src', $Config.get('webuser')['anketa']['info']['square_photo_url']);
        $("div.layer-photoline-purchase").show();
    }
};