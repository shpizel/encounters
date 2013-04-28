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
            $("div.layer-send-gift textarea").focus();
        });

        var $sendGiftFunction = function() {
            var $giftId = $("div.layer-send-gift .list-present_item-selected").attr('gift_id');
            var $comment = $("div.layer-send-gift textarea").val();
            var $currentUserId = $Config.get('current_user_id');

            $Tools.ajaxPost('messenger.gift.send', {'gift[id]': $giftId, 'gift[comment]': $comment, 'current_user_id': $currentUserId}, function($data) {
                if ($data.status == 0 && $data.message == "") {
                    $Account.setAccount($data.data.account);

                    $Profile.addGift(
                        $data.data.gift.url,
                        $data.data.gift.comment,
                        $data.data.gift.sender.user_id,
                        $data.data.gift.sender.name,
                        $data.data.gift.sender.age,
                        $data.data.gift.sender.city
                    );

                    $("div#overflow").hide();
                    $("div.app-layer").hide();
                } else if ($data.status == 3) {
                    $Layers.showAccountLayer({'status': $data.status});
                }
            });

            return false;
        };

        $("div.layer-send-gift textarea").keypress(function($event) {
            if ($event.ctrlKey && $event.keyCode == 13) {

            } else if ($event.keyCode == 13) {
                if ($("div.layer-send-gift .list-present_item-selected").length > 0) {
                    return $sendGiftFunction();
                }

                return false;
            }
        });

        $("div.layer-send-gift .form-send_gift input[type=submit]").click(function() {
            if ($("div.layer-send-gift .list-present_item-selected").length > 0) {
                return $sendGiftFunction();
            }

            return false;
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