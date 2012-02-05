/**
 * Search
 *
 * @author shpizel
 */
$Search = {

    /**
     * Storage
     *
     * @var object
     */
    $storage: {

    },

    /**
     * Инициализация интерфейса
     *
     * @return null
     */
    initUI: function() {
        this.initDecisionButtons();
    },

    /**
     * Залочить UI
     *
     * @lock UI
     */
    lockUI: function() {
        $("div.app-meet-button").fadeTo('fast', 0.6);
        $("div.app-lenta-img").hide();
        $("div.app-info-user").hide();
        $("p.app-see-block").fadeTo('fast', 0.6);
        $("div.app-image-member img").hide();
        $Search.$storage['locked'] = true;
    },

    /**
     * Разлочить UI
     *
     * @unlock UI
     */
    unlockUI: function() {
        $Search.$storage['locked'] = false;
        this.showNextPhoto();
    },

    /**
     * Инициализирует кнопки
     *
     * @init decision buttons
     */
    initDecisionButtons: function() {
        $("div.app-meet-button a.yes").click(function(){return $Search.makeDecision(1);});
        $("div.app-meet-button a.maybe").click(function(){return $Search.makeDecision(0);});
        $("div.app-meet-button a.no").click(function(){return $Search.makeDecision(-1);});
    },

    /**
     * Отправляет голосование и показывает следующую фотку
     *
     * @request vote.set
     */
    makeDecision: function($decision) {
        if ($Search.$storage['locked']) {
            return;
        }

        $.post($Routing.getVoteSetter(), { user_id: $Search.$storage['currentQueueElement']['info']['id'], decision: $decision }, function($data) {
            if ($data.status == 0 && $data.message == "") {
                $Search.showNextPhoto();
            } else {
                $status = $data.status;
                $message = $data.message;

                alert($status + ": " + $message);
            }
        }, 'json');
    },

    /**
     * Показывает следующую фотографию из очереди
     *
     * @queue next
     */
    showNextPhoto: function() {
        if (this.$storage['currentQueueElement'] = $currentQueueElement = $Queue.get()) {
            if ($currentQueueElement['photos'].length > 1) {
                $("div.app-lenta-img").html("");
                for (var $i=0;$i<$currentQueueElement['photos'].length;$i++) {
                    var $html = "<a class='" + (!$i ? 'select' : '') + "' href=\"#\" pid='" + $i + "' style=\"background-image: url('" +  $currentQueueElement['photos'][$i]['small_photo_url'] + "')\"></a>";
                    $("div.app-lenta-img").append($html);
                }

                $("div.app-lenta-img a").click(function() {
                    $("div.app-image-member img").attr('src', $currentQueueElement['photos'][$(this).attr('pid')]['huge_photo_url']);
                    $('div.app-lenta-img a').removeClass('select');
                    $(this).addClass('select');
                });

                $("div.app-lenta").show();
            } else {
                $("div.app-lenta").hide();
            }


            $("div.app-info-user a").html($currentQueueElement['info']['name']).attr({target:'_blank','href':$Config.get('platform').partner_url + "anketa.phtml?oid=" + $currentQueueElement['info']['id']});
            if ($currentQueueElement['info']['age']) {
                $("div.app-info-user span").html($currentQueueElement['info']['age']);
            } else {
                $("div.app-info-user span").html("");
            }

            if ($currentQueueElement['info']['gender'] == 'F') {
                $("div.app-info-user i").removeClass('male');
                $("div.app-info-user i").addClass('female');
            } else {
                $("div.app-info-user i").removeClass('female');
                $("div.app-info-user i").addClass('male');
            }

            $("div.app-meet-button").fadeTo('normal', 1);
            $("div.app-lenta-img").show();
            $("div.app-info-user").show();
            $("p.app-see-block").fadeTo('normal', 1);
            $("div.app-image-member img").attr({'src': $currentQueueElement['photos'][0]['huge_photo_url']});
            $("div.app-image-member img").show();

        } else {
            this.loadQueue(function(){
                return $Search.unlockUI();
            });
            return this.lockUI();
        }
    },

    /**
     * Запуск страницы
     *
     * @run page
     */
    run: function() {
        if (!$Queue.qsize()) {
            this.loadQueue(function(){
                return $Search.unlockUI();
            });
            return this.lockUI();
        }
    },

    /**
     * Загрузчик текущей очереди
     *
     * @param $callback
     */
    loadQueue: function($callback) {
        $.post($Routing.getCurrentQueueGetter(), function($data) {
            if ($data.status == 0 && $data.message == "") {
                for (var $i=0;$i<$data.data.length;$i++) {
                    $Queue.put($data.data[$i]);
                }

                $callback();
            } else {
                $status = $data.status;
                $message = $data.message;

                if ($status == 1) {
                    window.setTimeout(function() {
                        $Search.loadQueue($callback);
                    }, 1000);
                } else if ($status == 2) {
                    window.setTimeout(function() {
                        $Search.loadQueue($callback);
                    }, 1000);
                }
            }
        }, 'json');
    }
}