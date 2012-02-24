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
            if (!$Config.get('debug')) {
                top.location = $Config.get('platform').partner_url + 'app_platform/?action=view&app_id=' + $Config.get('platform').app_id;
            }
        } else {
            var $documentHeight = $('#wrapper').height();
            mamba.init(function() {
                mamba.method("resizeWindow", '100%', ($documentHeight > 1000) ? $documentHeight : 1000)
            });
        }

        $("div.notification a.close").click(function() {
            $.post($Routing.getPath('notification.remove'), function($data) {
                if ($data.status == 0 && $data.message == "") {
                    $("div.notification").hide();
                }
            });

            return false;
        });

        $("li.item-popul").click(function() {
            $Layers.showPopularityLayer();
        });

        this['init' + $Tools.ucfirst($route) + 'UI']();
        $Layers.initUI();

        return this;
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
    },

    /**
     * Billing UI
     *
     * @init UI
     */
    initBillingUI: function() {
        $Billing.initUI();
    }
}