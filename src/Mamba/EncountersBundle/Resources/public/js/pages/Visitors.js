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
        $(".closed a.ln").click(function() {
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
                        //$("div[user_id=" + $dataArray['user_id'] + "]").removeClass('closed').addClass('no');
                    } else if ($data.decision == 0) {
                        $Layers.showAnswerMaybeLayer($dataArray);
                        //$("div[user_id=" + $dataArray['user_id'] + "]").removeClass('closed').addClass('maybe');
                    } else if ($data.decision == 1) {
                        $Layers.showAnswerYesLayer($dataArray);
                        //$("div[user_id=" + $dataArray['user_id'] + "]").removeClass('closed').addClass('yes');
                    }
                } else if ($data.status == 3) {
                    $Layers.showEnergyLayer($dataArray);
                }
            }, 'json');

            return false;
        });

        $(".content div.info a").click(function() {
            var $source = $(this).parent().parent();
            var $userId = $source.attr('user_id');

            $Layers.showUserInfoLayer($Config.get('users')[$userId]);

            return false;
        });

        $(".content div.link a").click(function() {
            var $source = $(this).parent().parent();
            var $userId = $source.attr('user_id');

            $Layers.showUserInfoLayer($Config.get('users')[$userId]);

            return false;
        });
    },

    /**
     * Инициализирует кнопки
     *
     * @init buttons
     */
    initButtons: function() {
        $("div.content div.info a.ui-btn").click(function() {
            var $extra = {service: {id: 3}};
            $.post($Routing.getPath('service.add'), $extra, function($data) {
                if ($data.status == 0 && $data.message == "") {
                    mamba.method('pay', 3, $.toJSON($extra));
                    location.href = $Routing.getPath("billing");
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