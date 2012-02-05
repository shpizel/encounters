/**
 * Interface
 *
 * @author shpizel
 */
$Interface = {

    /**
     * UI Prepare
     *
     * @run page's UI init method
     * @return $Interface
     */
    init: function($route) {

        if (window.top == window.self) {
            top.location = $Config.get('platform').partner_url + 'app_platform/?action=view&app_id=' + $Config.get('platform').app_id;
        } else {
            mamba.init(function() {
                mamba.method("resizeWindow", 770, 1000);
//                window.setInterval(function() {
//                    $Interface.autoresize();
//                }, 1000);
            });
        }

        this['init' + Tools.ucfirst($route) + 'UI']();
        return this;
    },

    /**
     * Автоматически ресайзит страницу
     */
    autoresize: function() {
        //mamba.method("resizeWindow", "100%", $(document).height() + 50);
    },

    /**
     * Search UI init
     *
     * @init UI
     */
    initSearchUI: function() {
        $Search.initUI();
    },

    /**
     * Preferences UI init
     *
     * @init UI
     */
    initPreferencesUI: function() {
        $Preferences.initUI();
    },

    /**
     * Mutual UI init
     *
     * @init UI
     */
    initMutualUI: function() {
        $Mutual.initUI();
    },

    /**
     * Visitors UI
     *
     * @init UI
     */
    initVisitorsUI: function() {
        $Visitors.initUI();
    },

    /**
     * Mychoice UI
     *
     * @init UI
     */
    initMychoiceUI: function() {
        $Mychoice.initUI();
    },

    /**
     * Profile UI
     *
     * @init UI
     */
    initProfileUI: function() {
        $Profile.initUI();
    }
}