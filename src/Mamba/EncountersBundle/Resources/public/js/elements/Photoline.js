/**
 * Photoline
 *
 * @author shpizel
 */
$Photoline = {

    /**
     * Очередь показа фотоленты
     *
     * @var array
     */
    $Queue: [],

    /**
     * Microtime последнего обновления фотоленты
     *
     * @var float
     */
    $lastUpdated: 0,

    /**
     * Server update timer name
     *
     * @var str
     */
    $serverUpdateTimerName: 'photoline-server-update-timer',

    /**
     * Queue update timer name
     *
     * @var str
     */
    $queueUpdateTimerName: 'photoline-queue-update-timer',

    /**
     * Блокировка
     *
     * @var bool
     */
    $locked: false,

    /**
     * Инициализация интерфейса
     *
     * @init UI
     */
    initUI: function($route) {
        $Photoline.$lastUpdated = $Config.get('microtime');

        $("body").append("<div id='photoline-info-layer'></div>");

        $("div.photoline a.add").click(function() {
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
            if (parseInt($age) > 0) {
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

        $Config.set($Photoline.$serverUpdateTimerName, window.setInterval(function() {
            if (!$Photoline.$locked) {
                $Photoline.$locked = true;

                $.post($Routing.getPath('photoline.get'), {'from': $Photoline.$lastUpdated}, function($data) {
                    if ($data.status == 0 && $data.message == "") {
                        var $items = $data.data['items'];
                        if ($items.length > 0) {
                            for (var $i=0;$i<$items.length;$i++) {
                                $Photoline.$Queue.push($items[$i]);
                            }

                            $Photoline.$lastUpdated = $data.data['microtime'];
                        }
                    }

                    $Photoline.$locked = false;
                }).onerror(function() {
                    window.clearInterval($Config.get($Photoline.$serverUpdateTimerName));
                });
            }
        }, 3 * 1000));

        $Config.set($Photoline.$queueUpdateTimerName, window.setInterval(function() {
            if (!$Photoline.$locked) {
                $Photoline.$locked = true;
                do {
                    var $photolineItem = $Photoline.$Queue.pop();
                    if ($photolineItem) {
                        var $html = $("<a><img src=\"" + $photolineItem['photo_url'] + "\"></a>").attr({
                            'user_id': $photolineItem['user_id'],
                            'name' : $photolineItem['name'],
                            'age'  : $photolineItem['age'],
                            'city' : $photolineItem['city'],
                            'comment' : $photolineItem['comment']
                        }).addClass('item');

                        $("div.photoline div.lenta a.pusher").css({'width': '0px'});
                        $html.insertAfter($("div.photoline div.lenta a.pusher"));

                        $("div.photoline div.lenta a.pusher").animate({'width': '+=62'}, 1000, function() {
                            $Photoline.$locked = false;
                        });
                    } else {
                        $Photoline.$locked = false;
                    }
                } while (false);
            }
        }, 0.5 * 1000));

        var $defaultPhotolineItems = $Config.get('photoline');
        $("div.photoline div.lenta a.pusher").css({'width': '0px'});

        for (var $i=$defaultPhotolineItems.length - 1;$i>=0; $i--) {
            var $photolineItem = $defaultPhotolineItems[$i];
            var $html = $("<a><img src=\"" + $photolineItem['photo_url'] + "\"></a>").attr({
                'user_id': $photolineItem['user_id'],
                'name' : $photolineItem['name'],
                'age'  : $photolineItem['age'],
                'city' : $photolineItem['city'],
                'comment' : $photolineItem['comment']
            }).addClass('item');

            $html.insertAfter($("div.photoline div.lenta a.pusher"));
        }

        $("div.photoline div.lenta a.pusher").animate({'width': '+=62'}, 1000);
    }
}