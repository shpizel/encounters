/**
 * Game
 *
 * @author shpizel
 */
$Game = {

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
        $("img#bigphoto").hide();
        $("div#photos").hide();
        $("div#buttons").hide();
        $("div#details").hide();
        $("div#loading").show();
    },

    /**
     * Разлочить UI
     *
     * @unlock UI
     */
    unlockUI: function() {
        this.showNextPhoto();
    },

    /**
     * Инициализирует кнопки
     *
     * @init decision buttons
     */
    initDecisionButtons: function() {
        $("button#yes").click(function(){return $Game.makeDecision(1);});
        $("button#maybe").click(function(){return $Game.makeDecision(0);});
        $("button#no").click(function(){return $Game.makeDecision(-1);});
    },

    /**
     * Отправляет голосование и показывает следующую фотку
     *
     * @request vote.set
     */
    makeDecision: function($decision) {
        $.post($Routing.getVoteSetter(), { user_id: $Game.$storage['currentQueueElement']['info']['id'], decision: $decision }, function($data) {
            if ($data.status == 0 && $data.message == "") {
                $Game.showNextPhoto();
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

            $("img#bigphoto").attr('src', $currentQueueElement['photos'][0]['huge_photo_url']);
            $("div#photos").html("");
            for (var $i=0;$i<$currentQueueElement['photos'].length;$i++) {
                $("div#photos").append("<img pid='" + $i + "' src='" + $currentQueueElement['photos'][$i]['small_photo_url']  + "'/> ");
            }

            $("div#photos img").click(function() {
                $("img#bigphoto").attr('src', $currentQueueElement['photos'][$(this).attr('pid')]['huge_photo_url']);
            });

            $("div#details").html("<a target='_blank' href='http://mamba.ru/anketa.phtml?oid=" +  $currentQueueElement['info']['id'] +  "'>"+$currentQueueElement['info']['name']+"</a>, " + $currentQueueElement['info']['gender'] + ", " + $currentQueueElement['info']['age']);

            $("img#bigphoto").show();
            $("div#photos").show();
            $("div#buttons").show();
            $("div#details").show();
            $("div#loading").hide();

        } else {
            this.loadQueue(function(){
                return $Game.unlockUI();
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
        if (window.$debug) {
            console.log("$Game has runned");
        }

        if (!$Queue.qsize()) {
            this.loadQueue(function(){
                return $Game.unlockUI();
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
                        $Game.loadQueue($callback);
                    }, 1000);
                } else if ($status == 2) {
                    window.setTimeout(function() {
                        $Game.loadQueue($callback);
                    }, 1000);
                }
            }
        }, 'json');
    }
}