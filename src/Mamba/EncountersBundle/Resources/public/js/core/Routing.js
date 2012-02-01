/**
 * Routing
 *
 * @author shpizel
 */
$Routing = {

    /**
     * Правила роутинга
     *
     * @var object
     */
    routes: {
        preferences: '/preferences',
        game: '/game',
        mutual: '/mutual',
        visitors: '/visitors',
        mychoice: '/mychoice',
        profile: '/profile'
    },

    /**
     * Возвращает правило маршрутизации
     *
     * @return string | null
     */
    getRoute: function($path) {
        for (var key in this.routes) {
            if ($path == this.routes[key]) {
                return key;
            }
        }
    }
}