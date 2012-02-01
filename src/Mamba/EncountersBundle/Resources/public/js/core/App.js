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
    init: function($path) {
        if (($route = $Routing.getRoute($path)) == 'game') {

            /** Инициализируем интерфейс */
            $Interface.init($route);
            return this;
        }
    },

    /** Запуск приложения */
    run: function() {

    }
}