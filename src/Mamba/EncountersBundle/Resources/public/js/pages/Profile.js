/**
 * Profile
 *
 * @author shpizel
 */
$Profile = {

    /**
     * Инициализация интерфейса
     *
     * @init
     */
    initUI: function() {

        /**
         * 1) клик по фотке
         * 2) клик по кнопке "отправить подарок"
         * 3) наведение курсора на гифты
         * 4)
         *
         * @author shpizel
         */

        $(document).click(function(){
            $(".orange-menu .drop-down").hide();
        });

//        $(".orange-menu .item-arrow").click(function() {
//            $(".orange-menu .drop-down").fadeIn('fast');
//            return false;
//        });

        $(".profile-picture").click(function() {
            //$Layers.showProfilePhotosLayer();
        });

        $(".button-present").click(function() {
            $Layers.showSendGiftLayer();
        });

        $(".orange-menu .message").click(function() {
            $Layers.openMessengerWindowFunction($Config.get('current_user_id'));
        });

        $(".orange-menu .meet").click(function() {
            top.location = $Config.get('platform').partner_url + 'app_platform/?action=view&app_id=' + $Config.get('platform').app_id + "&extra=meet" + $Config.get('current_user_id');
        });

        $("div.list-present_user").on('mouseenter', "a.list-present_user-item", function($event) {
            var $this = $(this);

            var $html = "<a href=\"/profile?id=" + $this.attr('user_id') + "\"><strong>" + $this.attr('name') + "</strong></a>, ";
            var $age = $this.attr('age');
            if (parseInt($age) > 0) {
                $html+= $age + ", ";
            }
            $html+= "<font color='#666'>" + $this.attr('city') + "</font>";

            var $comment = $this.attr('comment');
            if ($comment) {
                $html+="<div style='padding-top:5px;'>" + $comment + '</div>';
            }

            var $giftPosition = $this.position();
            var $blockPosition = $(".anketa-content_user-present").position();
            var $photolineInfoLayer = $("div#photoline-info-layer");
            $photolineInfoLayer.html($html).css({'top': ($giftPosition.top + $blockPosition.top + 80) +'px'});

            var $photolineContainer = $("div.photoline");
            var $photolineWidth = $photolineContainer.width();
            var $photolineInfoLayerWidth = $photolineInfoLayer.width();


            if ($giftPosition.left + $photolineInfoLayerWidth > $photolineWidth) {
                $photolineInfoLayer.css({left: $photolineWidth - $photolineInfoLayerWidth - 10 + 'px'});
            } else {
                $photolineInfoLayer.css({left: $giftPosition.left + $blockPosition.left + 2 + 'px'});
            }
            $photolineInfoLayer.show();
        });

        $("div#photoline-info-layer").on('mouseleave', /*"a:not(.add)",*/ function($event) {
            $("div#photoline-info-layer").hide();
        });
    },

    /**
     * Добавляет гифт на страницу
     *
     * @param $url
     * @param $city
     * @param $senderId
     * @param $senderName
     * @param $senderCity
     */
    addGift: function($url, $comment, $senderId, $senderName, $senderAge, $senderCity) {
        $(".anketa-content_user-present").removeClass('no-present');
        var $html = $("<a><img src=\"" + $url + "\"></a>").attr({
            'user_id': $senderId,
            'name' : $senderName,
            'age'  : $senderAge,
            'city' : $senderCity,
            'comment' : $comment,
            'href': "#",
            'target': '_self'
        }).addClass('list-present_user-item');

        if ($(".list-present_user a.list-present_user-item").length > 0) {
            $html.insertBefore($(".list-present_user a.list-present_user-item").eq(0));
        } else {
            $html.appendTo($(".list-present_user"));
        }
    },

    /**
     * Запуск страницы
     *
     * @run page
     */
    run: function() {

    }
}