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

    },

    /**
     * Разлочить UI
     *
     * @unlock UI
     */
    unlockUI: function() {

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
     * Отправляет голосование
     *
     * @request vote.set
     */
    makeDecision: function($decision) {
        alert($decision);
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

        this.$storage['currentQueueElement'] = $currentQueueElement = $Queue.get();

        $("img#bigphoto").attr('src', $currentQueueElement['photos'][0]['huge_photo_url']);
        for (var $i=0;$i<$currentQueueElement['photos'].length;$i++) {
            $("div#photos").append("<img pid='" + $i + "' src='" + $currentQueueElement['photos'][$i]['small_photo_url']  + "'/> ");
        }

        $("div#photos img").click(function() {
            $("img#bigphoto").attr('src', $currentQueueElement['photos'][$(this).attr('pid')]['huge_photo_url']);
        });
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

                } else if ($status == 2) {

                }
            }
        }, 'json');
    }
}