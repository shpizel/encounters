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