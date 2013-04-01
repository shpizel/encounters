/**
 * Visitors
 *
 * @author shpizel
 */
$Visitors = {

    /**
     * Инициализация интерфейса
     *
     * @init UI
     */
    initUI: function() {
        this.initButtons();

        var $showLayerFunction = function() {
            var $source = $(this).parent().parent();

            var $dataArray = {
                'user_id': $source.attr('user_id'),
                'name': $source.attr('name'),
                'small_photo_url': $source.attr('small_photo_url'),
                'medium_photo_url': $source.attr('medium_photo_url')
            };

            $.post($Routing.getPath('decision.get'), { user_id: $dataArray['user_id']}, function($data) {
                if ($data.status == 0 && $data.message == "") {
                    $data = $data.data;
                    if ($data.hasOwnProperty('charge')) {
                        $Battery.setCharge($data.charge);
                    }

                    if ($data.hasOwnProperty('account')) {
                        $Account.setAccount($data.account);
                    }

                    if ($data.decision === false) {
                        $Layers.showAnswerNotSeeYetLayer($dataArray);
                    } else if ($data.decision == -1) {
                        $Layers.showAnswerNoLayer($dataArray);
                    } else if ($data.decision == 0) {
                        $Layers.showAnswerMaybeLayer($dataArray);
                    } else if ($data.decision == 1) {
                        $Layers.showAnswerYesLayer($dataArray);
                    }
                } else if ($data.status == 3) {
                    $Layers.showEnergyLayer($dataArray);
                }
            });

            return false;
        };

//        $(".closed a.ln").click(function() {
//            return $showLayerFunction();
//        });

        //$(".content div.photoListItem div.info a").click($showLayerFunction);
        $(".content div.photoListItem div.link a").click($showLayerFunction);
    },

    /**
     * Инициализирует кнопки
     *
     * @init buttons
     */
    initButtons: function() {
        $("div.visitors div.pictures div.title a.ui-btn").click(function() {
            $.post($Routing.getPath('popularity.get'), function($data) {
                if ($data.status == 0 && $data.message == "") {
                    var
                        $energy = $data.data['popularity']['energy'],
                        $next = $data.data['popularity']['next'],
                        $prev = $data.data['popularity']['prev'],
                        $level = $data.data['popularity']['level']
                        ;

                    $Config.$storage['webuser']['popularity'] = $data.data['popularity'];

                    $(".info-meet li.item-popularity div.bar div.level-background").attr('class', 'level-background lbc' + (parseInt(($energy - $prev)*100/($next - $prev)/25) + 1));
                    $(".info-meet li.item-popularity div.bar div.level").attr('class', 'level l' + $level);
                    $(".info-meet li.item-popularity div.bar div.speedo").css('width', parseInt(($energy - $prev)*100/($next - $prev)*0.99)+'px');

                    $Account.setAccount($data.data['account']);

                    $("div.app-block-no-popular").hide();

                    $("div#overflow").hide();
                    $("div.app-layer").hide();
                } else if ($data.status == 3) {
                    $Layers.showAccountLayer({'status': $data.status});
                }
            });

            return false;
        });
    },

    /**
     * Запуск страницы
     *
     * @run page
     */
    run: function() {

    }
}