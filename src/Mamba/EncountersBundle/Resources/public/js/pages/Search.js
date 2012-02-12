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
        $("img.app-image").click(function() {
            $Search.listNextPhoto();
        });
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
        $("a.rarr").hide();
        $("a.larr").hide();
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

        $("a.larr").click(function() {
            $Search.listPreviousPhoto();
            return false;
        });

        $("a.rarr").click(function() {
            $Search.listNextPhoto();
            return false;
        });

        $("div.message-help a#getmore").click(function() {
//            $Layers.showAnswerMaybeLayer();
//            $Layers.showAnswerNoLayer();
//            $Layers.showAnswerYesLayer();
//            $Layers.showEnergyLayer();
//            $Layers.showPopularityLayer();
//            $Layers.showAnswerNotSeeYetLayer();
            $Layers.showMutualLayer();
            return false;
        });
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
        var $currentQueueElement;

        if (this.$storage['currentQueueElement'] = $currentQueueElement = $Queue.get()) {
            this.$storage['currentPhotoNumber'] = 0;
            this.rebuildThumbsPanel();

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
     * Перестроить панель тумб
     *
     * @rebuild thumbs
     */
    rebuildThumbsPanel: function() {
        var
            $currentQueueElement = this.$storage['currentQueueElement'],
            $photosCount = $currentQueueElement['photos'].length
        ;

        if ($currentQueueElement['photos'].length > 1) {

            var
                $html = "",
                $currentPhotoNumber = this.$storage['currentPhotoNumber'],
                $photosPerPage = 6
            ;

            if ($currentQueueElement['photos'].length-1 < $photosPerPage) {
                $("a.rarr").hide();
                $("a.larr").hide();
            } else {
                $("a.rarr").show();
                $("a.larr").show();
            }

            console.log('current photo number: '+$currentPhotoNumber);
            console.log('photos count: '+ $photosCount);

            var $withOffset = ($currentPhotoNumber >= ($photosCount > $photosPerPage ? $photosPerPage: $photosCount));
            console.log('with offset: ' + $withOffset);
            var $from = ($withOffset ? ($currentPhotoNumber + 1 - ($photosCount > $photosPerPage ? $photosPerPage: $photosCount)) : 0);
            if ($from + $photosPerPage >= $photosCount) {
                var $to = $photosCount;
            } else {
                var $to = $from + $photosPerPage;
            }

            console.log('from:' + $from);
            console.log('to: ' + $to);

            for (var $i = $from;$i<$to;$i++) {
                $html += "<a class='" + ($i == this.$storage['currentPhotoNumber'] ? 'select' : '') + "' href=\"#\" pid='" + $i + "' style=\"background-image: url('" +  $currentQueueElement['photos'][$i]['small_photo_url'] + "')\"></a>";
            }

            $("div#thumbs").html($html);

            $("div#thumbs a").click(function() {
                $("div.app-image-member img").attr('src', $currentQueueElement['photos'][$Search.$storage['currentPhotoNumber'] = $(this).attr('pid')]['huge_photo_url']);
                $('div.app-lenta-img a').removeClass('select');
                $(this).addClass('select');
                return false;
            });

            $("div.app-lenta").show();
        } else {
            $("div.app-lenta").hide();
        }
    },

    /**
     * Показывает следующую фотку по списку минифоток
     *
     * @shows next pic
     */
    listNextPhoto: function() {
        var
            $currentId = parseInt(this.$storage['currentPhotoNumber']),
            $size      = this.$storage['currentQueueElement'].photos.length
        ;

        var $nextId;

        if (($nextId = $currentId + 1) >= $size) {
            $nextId = 0;
        }

        $("div.app-image-member img").attr('src', this.$storage['currentQueueElement']['photos'][this.$storage['currentPhotoNumber'] = $nextId]['huge_photo_url']);
        this.rebuildThumbsPanel();
    },

    /**
     * Показывает предыдущую фотку по списку минифоток
     *
     * @shows prev pic
     */
    listPreviousPhoto: function() {
        var
            $currentId = parseInt(this.$storage['currentPhotoNumber']),
            $size      = this.$storage['currentQueueElement'].photos.length
            ;

        var $nextId;

        if (($nextId = $currentId - 1) < 0) {
            $nextId = this.$storage['currentQueueElement']['photos'].length - 1;
        }

        $("div.app-image-member img").attr('src', this.$storage['currentQueueElement']['photos'][this.$storage['currentPhotoNumber'] = $nextId]['huge_photo_url']);
        this.rebuildThumbsPanel();
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
                    }, 1750);
                } else if ($status == 2) {
                    window.setTimeout(function() {
                        $Search.loadQueue($callback);
                    }, 1750);
                }
            }
        }, 'json');
    }
}