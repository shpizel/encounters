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
    },

    /**
     * Инициализирует кнопки
     *
     * @init buttons
     */
    initButtons: function() {
        $("div.content div.info a.ui-btn").click(function() {
            mamba.method('openPaymentLayer', $Config.get('platform').app_id, 1);
        });
    },

    /**
     * Запуск страницы
     *
     * @run page
     */
    run: function() {
        if (window.$debug) {
            console.log("$Visitors has runned");
        }
    }
}