/**
 * Mutual
 *
 * @author shpizel
 */
$Mutual = {

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

        //$("div.content div.photoListItem div.info a").click($showLayerFunction);
        $("div.content div.photoListItem div.link a").click($showLayerFunction);
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
        });

        $("div.content div.pictures div.photoListItem a.close").click(function() {
            var $parent = $(this).parent();
            var $userId = $parent.attr('user_id');

            if (confirm("Вы уверены, что хотите удалить этого пользователя из «Взаимных»?")) {
                $.post($Routing.getPath('decision.remove'), {'user_id': $userId}, function($data) {
                    if ($data.status == 0 && $data.message == "") {
                        $parent.hide();

                        $data = $data.data;
                        if ($data.hasOwnProperty('counters')) {
                            if ($data['counters']['mychoice'] > 0) {
                                $('li.item-mychoice a i').eq(0).text($data['counters']['mychoice']);
                            }

                            if ($data['counters']['visitors'] > 0 ) {
                                $('li.item-visitors a i').eq(1).text($data['counters']['visitors']);
                            }

                            if ($data['counters']['mutual'] > 0) {
                                $('li.item-mutual a i').eq(0).text($data['counters']['mutual']);
                            }
                        }
                    }
                });
            }
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