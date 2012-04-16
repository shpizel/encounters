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
                }
            });
        });

        $("div.content div.pictures div.mpic a.close").click(function() {
            var $parent = $(this).parent();
            var $userId = $parent.attr('user_id');

            if (confirm("Вы уверены, что хотите удалить этого пользователя из «Взаимных»?")) {
                $.post($Routing.getPath('decision.remove'), {'user_id': $userId}, function($data) {
                    if ($data.status == 0 && $data.message == "") {
                        $parent.hide();
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