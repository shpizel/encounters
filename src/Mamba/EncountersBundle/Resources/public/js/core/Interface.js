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

        if (window.location.href.indexOf('ym_playback') < 0 && document.referrer.indexOf('metrika.yandex') < 0) {
            if ((window.top == window.self) && (!$Config.get('debug'))) {
                top.location = $Config.get('platform').partner_url + 'app_platform/?action=view&app_id=' + $Config.get('platform').app_id;
            } else {
                var $documentHeight = $('#wrapper').height();
                if ($Config.get('debug')) {
                    $documentHeight += 45;
                }

                mamba.init(function() {
                    mamba.method("resize", '100%', ($documentHeight > 1000) ? $documentHeight : 1000);
                    mamba.on('paymentSuccess', function($data) {
                        location.href = $Routing.getPath("billing");
                    });

                    var $dimensionsSetterFunction = function ($data) {
                        $Config.set('dimensions', {
                            'width'     : $data.data.width,
                            'height'    : $data.data.height,
                            'offsetLeft': $data.data.offsetLeft,
                            'offsetTop' : $data.data.offsetTop,
                            'scrollTop' : $data.data.scrollTop
                        });
                    };

                    mamba.on('resize', $dimensionsSetterFunction);
                    mamba.on('scroll', $dimensionsSetterFunction);
                    mamba.on('dimensions', $dimensionsSetterFunction);

                    mamba.on('messageComplete', function($data) {
                        alert('Классно :)');
                    });

                    mamba.on('messageCancel', function($data) {
                        alert('Ой :(');
                    });

                }, function() {
                    top.location.href = $Config.get('platform')['partner_url'] + 'app_platform/?action=view&app_id=' + $Config.get('platform')['app_id'];
                });
            }
        }

        $("div.notification a.close").click(function() {
            $.post($Routing.getPath('variable.set'), {'key': 'notification_hidden', 'data': 1}, function($data) {
                if ($data.status == 0 && $data.message == "") {
                    $("div.notification").hide();

                }
            });

            return false;
        });

        if ($route != 'welcome') {
            $Battery.initUI();
            $Speedo.initUI();
            $Account.initUI();
        }

        $Photoline.initUI();

        this['init' + $Tools.ucfirst($route) + 'UI']();
        $Layers.initUI();
        return this;
    },

    /**
     * Welcome UI init
     *
     * @init UI
     */
    initWelcomeUI: function() {
        $Welcome.initUI();
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