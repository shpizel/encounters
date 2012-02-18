/**
 * Billing
 *
 * @author shpizel
 */
$Billing = {

    /**
     * Инициализация интерфейса
     *
     * @init
     */
    initUI: function() {
        $("a.billing").click(function() {
            window.history.back();
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