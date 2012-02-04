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