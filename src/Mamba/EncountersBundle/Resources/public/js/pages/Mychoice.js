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
        $(".mcpic a.ln").click(function() {
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
            }, 'json');
        });

        $(".content div.info a").click(function() {
            var $source = $(this).parent().parent();
            var $userId = $source.attr('user_id');

            $Layers.showUserInfoLayer($Config.get('users')[$userId]);

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