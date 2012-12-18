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
        $("div.photoline div.lenta a.add").click(function() {
            $.post($Routing.getPath('photoline.purchase'), function($data) {
                if ($data.status == 0 && $data.message == "") {
                    $Account.setAccount($data.data['account']);
                } else if ($data.status == 3) {
                    $Layers.showAccountLayer({'status':$data.status});
                }
            });

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
                'title': $photolineItem['name'] + ", " + $photolineItem['age'] + ", " + $photolineItem['city']
            }).addClass('item');
            $("div.photoline div.lenta").append($html);
        }
    }
}