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
        var $microtime = $Config.get('microtime');
        if ($microtime) {
            $Photoline.$lastUpdated = $microtime;
        }

        $("body").append("<div id='photoline-info-layer'></div>");

        $("div.photoline a.add").click(function() {
            $Layers.showPhotolinePurchaseLayer();
            return false;
        });

        $("div.photoline div.lenta").on('mouseenter', "a:not(.add)", function($event) {
            var $this = $(this);

            var $html = "<strong>" + $this.attr('name') + "</strong>, ";
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
            var $photolineInfoLayer = $("div#photoline-info-layer");
            $photolineInfoLayer.html($html).css({'top': ($photoPosition.top + 62 + 9) +'px'});

            var $photolineContainer = $("div.photoline");
            var $photolineWidth = $photolineContainer.width();
            var $photolineInfoLayerWidth = $photolineInfoLayer.width();


            if ($photoPosition.left + $photolineInfoLayerWidth > $photolineWidth) {
                $photolineInfoLayer.css({left: $photolineWidth - $photolineInfoLayerWidth - 10 + 'px'});
            } else {
                $photolineInfoLayer.css({left: $photoPosition.left + 2 + 'px'});
            }

            $photolineInfoLayer.show();
        });

        $("div.photoline div.lenta").on('mouseleave', "a:not(.add)", function($event) {
            $("div#photoline-info-layer").hide();
        });

        $Config.set($Photoline.$serverUpdateTimerName, window.setInterval(function() {
            if (!$Photoline.$locked) {
                $Photoline.$locked = true;

                $Tools.ajaxPost('photoline.get', {'from': $Photoline.$lastUpdated}, function($data) {
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
                },function() {
                    window.clearInterval($Config.get($Photoline.$serverUpdateTimerName));
                });
            }
        }, 7.5 * 1000));

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
                            'comment' : $photolineItem['comment'],
                            'href': $Config.get('platform')['partner_url'] + 'app_platform/?action=view&app_id=355&extra=profile' + $photolineItem['user_id'],
                            'target': '_top'
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
        }, 1 * 1000));

        var $defaultPhotolineItems = $Config.get('photoline');
        if ($defaultPhotolineItems) {
            $("div.photoline div.lenta a.pusher").css({'width': '62px'});

            for (var $i=$defaultPhotolineItems.length - 1;$i>=0; $i--) {
                var $photolineItem = $defaultPhotolineItems[$i];
                var $html = $("<a><img src=\"" + $photolineItem['photo_url'] + "\"></a>").attr({
                    'user_id': $photolineItem['user_id'],
                    'name' : $photolineItem['name'],
                    'age'  : $photolineItem['age'],
                    'city' : $photolineItem['city'],
                    'comment' : $photolineItem['comment'],
                    'href': $Config.get('platform')['partner_url'] + 'app_platform/?action=view&app_id=355&extra=profile' + $photolineItem['user_id'],
                    'target': '_top'
                }).addClass('item');

                $html.insertAfter($("div.photoline div.lenta a.pusher"));
            }
        }
    }
}