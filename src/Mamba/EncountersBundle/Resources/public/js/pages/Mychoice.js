/**
 * Mychoice
 *
 * @author shpizel
 */
$Mychoice = {

    /**
     * Инициализация интерфейса
     *
     * @init
     */
    initUI: function() {
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

        $("div.content div.closed a.ln").click($showLayerFunction);
        //$("div.content div.photoListItem div.info a").click($showLayerFunction);
        $("div.content div.waiting div.link a").click($showLayerFunction);
        $("div.content div.maybe div.link a").click($showLayerFunction);
        $("div.content div.yes div.link a").click($showLayerFunction);
        $("div.content div.no div.link a").click($showLayerFunction);
    },

    /**
     * Запуск страницы
     *
     * @run page
     */
    run: function() {

    }
}