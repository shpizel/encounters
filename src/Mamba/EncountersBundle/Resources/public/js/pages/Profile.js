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

        $(document).click(function(){
            $(".orange-menu .drop-down").hide();
        });

//        $(".orange-menu .item-arrow").click(function() {
//            $(".orange-menu .drop-down").fadeIn('fast');
//            return false;
//        });

        $(".profile-picture").click(function() {
            if ($Config.get('photos').length > 0) {
                $Layers.showProfilePhotosLayer();
            }
        });

        $("a#anketa-open").click(function() {
            if ($(this).attr('href') != '#') {
                $(this).attr('target', '_blank');
                return true;
            }

            $Tools.ajaxPost('decision.get', { user_id: $(this).attr('profile_id')}, function($data) {
                if ($data.status == 0 && $data.message == "") {
                    $data = $data.data;
                    if ($data.hasOwnProperty('charge')) {
                        $Battery.setCharge($data.charge);
                    }

                    if ($data.hasOwnProperty('account')) {
                        $Account.setAccount($data.account);
                    }

                    $(this).attr('target', '_blank').attr('href', $Config.get('platform').partner_url + 'anketa.phtml?oid=' + $(this).attr('profile_id'));
                    alert('Все хорошо. Нажмите на ссылку еще раз :)');
                } else if ($data.status == 3) {
                    alert('Услуга стоит 1 ячейку батарейки. Зарядите батарейку за 1 монету!');
                    mamba.method('pay', 1, $.toJSON({service: {id: 1}}));
                    location.href = $Routing.getPath("billing");
                }
            });

            return false;
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

            var $html = "<a target=\"_top\" href=\"" + $Config.get('platform').partner_url + 'app_platform/?action=view&app_id=' + $Config.get('platform').app_id + "&extra=profile" + $this.attr('user_id') + "\"><strong>" + $this.attr('name') + "</strong></a>, ";
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