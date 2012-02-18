/**
 * Routing
 *
 * @author shpizel
 */
$Routing = {

    /**
     * Устанавливает правила маршрутизации
     *
     * @param $routes
     */
    setRoutes: function($routes) {
        this.$routes = $routes;
    },

    /**
     * Возвращает правило маршрутизации
     *
     * @return string | null
     */
    getRoute: function($path) {
        for (var $key in this.$routes) {
            if ($path == this.$routes[$key]) {
                return $key;
            }
        }
    },

    /**
     * Возвращает путь по ключу
     *
     * @param $route
     */
    getPath: function($route) {
        return this.$routes[$route];
    }
}