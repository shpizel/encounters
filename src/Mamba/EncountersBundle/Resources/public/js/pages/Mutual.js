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
                    mamba.method('openPaymentLayer', $Config.get('platform').app_id, 1);
                    location.href = $Routing.getPath("billing");
                }
            });

//            return false;
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