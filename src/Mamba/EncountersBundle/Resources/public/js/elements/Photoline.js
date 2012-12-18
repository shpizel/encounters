/**
 * Photoline
 *
 * @author shpizel
 */
$Photoline = {

    /**
     * Инициализация интерфейса
     *
     * @init UI
     */
    initUI: function($route) {
        $("body").append("<div id='photoline-info-layer'></div>");

        $("div.photoline div.lenta a.add").click(function() {
            $Layers.showPhotolinePurchaseLayer();

            return false;
        });

        $("div.photoline div.lenta").on('click', "a:not(.add)", function($event) {
            var $this = $(this);
            $.post($Routing.getPath('photoline.choose'), {'user_id': $this.attr('user_id')}, function($data) {
                if ($data.status == 0 && $data.message == "") {
                    top.location = $Config.get('platform').partner_url + 'app_platform/?action=view&app_id=' + $Config.get('platform').app_id + "&extra=" + $this.attr('user_id');
                } else if ($data.status == 3) {
                    alert('Вы уже голосовали за этого пользователя');
                } else if ($data.status == 4) {
                    alert('Вы не можете голосовать за себя');
                }
            });
        });

        $("div.photoline div.lenta").on('mouseenter', "a:not(.add)", function($event) {
            var $this = $(this);

            var $html = "<b>" + $this.attr('name') + "</b>, ";
            var $age = $this.attr('age');
            if ($age) {
                $html+= $age + ", ";
            }
            $html+= "<font color='#666'>" + $this.attr('city') + "</font>";

            var $comment = $this.attr('comment');
            if ($comment) {
                $html+="<div style='padding-top:5px;'>" + $comment + '</div>';
            }

            var $photoPosition = $this.position();
            $("div#photoline-info-layer").html($html).css({'top': ($photoPosition.top + 62 + 9) +'px', 'left': $photoPosition.left +'px'}).show();
        });

        $("div.photoline div.lenta").on('mouseleave', "a:not(.add)", function($event) {
            $("div#photoline-info-layer").hide();
        });

        $Photoline.render();

        $Config.set('photoline-timer', window.setInterval(function() {
            $Photoline.update();
        }, 5000));
    },

    /**
     * Получение новых игроков
     *
     *
     */
    update: function() {
        $.post($Routing.getPath('photoline.get'), function($data) {
            if ($data.status == 0 && $data.message == "") {
                $Config.set('photoline', $data.data['items']);
                $Photoline.render();
            } else {
                window.clearInterval($Config.get('photoline-timer'));
            }
        }).onerror(function() {
            window.clearInterval($Config.get('photoline-timer'));
        });
    },

    /**
     * Перерисовать мордоленту
     *
     */
    render: function() {
        $("div.photoline div.lenta a:not(.add)").remove();
        var $photoline = $Config.get('photoline');

        for (var i=0;i<$photoline.length;i++) {
            var $photolineItem = $photoline[i];
            var $html = $("<a><img src=\"" + $photolineItem['photo_url'] + "\"></a>").attr({
                'user_id': $photolineItem['user_id'],
                'name' : $photolineItem['name'],
                'age'  : $photolineItem['age'],
                'city' : $photolineItem['city'],
                'comment' : $photolineItem['comment']
            }).addClass('item');
            $("div.photoline div.lenta").append($html);
        }
    }
}