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
    },

    /**
     * Инициализирует кнопки
     *
     * @init buttons
     */
    initButtons: function() {
        $("div.content div.info a.ui-btn").click(function() {
            $.post($Routing.getPath('service.add'), {service: {id: 3}}, function($data) {
                if ($data.status == 0 && $data.message == "") {
                    mamba.method('pay', 3);
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