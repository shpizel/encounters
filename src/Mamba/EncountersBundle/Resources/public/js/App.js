/**
 * Application
 *
 * @author shpizel
 */
$App = {

    /**
     * Конструктор
     *
     * @return App
     */
    init: function() {

        /** Инициализируем интерфейс */
        $Interface.init();
        
        return this;
    },

    /**
     * Запуск приложения
     *
     * @return null
     */
    run: function() {
        alert('run');
    }
}