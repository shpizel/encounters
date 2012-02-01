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
        if ((this.$route = $Routing.getRoute($path)) == 'game') {

            /** Инициализируем интерфейс */
            $Interface.init(this.$route);
            return this;
        }
    },

    /** Запуск приложения */
    run: function($debug) {
        window["$" + Tools.ucfirst(this.$route)].run($debug);
    }
}