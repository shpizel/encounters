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
    $routes: {

        /**
         * Основные правила маршрутизации
         *
         * @author shpizel
         */
        preferences : '/preferences',
        game        : '/game',
        mutual      : '/mutual',
        visitors    : '/visitors',
        mychoice    : '/mychoice',
        profile     : '/profile',

        /**
         * AJAX-маршрутизация
         *
         * @author shpizel
         */
        ajax: {
            'queue.get': "/ajax/queue.get",
            'vote.set': "/ajax/vote.set"
        }
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
     * Возвращает путь для взятия текущей очереди пользователя
     *
     * @return string
     */
    getCurrentQueueGetter: function() {
        return this.$routes['ajax']['queue.get'];
    },

    /**
     * Возвращает путь для постинга результата выбора
     *
     * @return string
     */
    getVoteSetter: function() {
        return this.$routes['ajax']['vote.set'];
    }
}