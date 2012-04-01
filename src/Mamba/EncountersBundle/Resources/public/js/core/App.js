/**
 * Application
 *
 * @author shpizel
 */
$App = {

    /**
     * Конструктор
     *
     * @return $App
     */
    init: function($path) {
        if (this.$route = $Routing.getRoute($path)) {

            /** Инициализируем интерфейс */
            $Interface.init(this.$route);

            /** Очередь для приглашений */
            /*$inviteQueue = jQuery.extend(true, {}, $Queue);
            $inviteQueue.$storage = [];*/

            return this;
        }
    },

    /**
     * Запуск приложения
     *
     * @run $App
     */
    run: function() {
        return window["$" + $Tools.ucfirst(this.$route)].run();
    }
}