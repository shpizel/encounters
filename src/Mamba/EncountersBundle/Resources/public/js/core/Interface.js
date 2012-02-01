/**
 * Interface
 *
 * @author shpizel
 */
$Interface = {

    /**
     * UI Prepare
     *
     * @return $Interface
     */
    init: function($route) {
        this['init' + Tools.ucfirst($route) + 'UI']();
        return this;
    },

    initGameUI: function() {
        $Game.initUI();
    },

    initPreferencesUI: function() {
        $Preferences.initUI();
    },

    initMutualUI: function() {
        $Mutual.initUI();
    },

    initVisitorsUI: function() {
        $Visitors.initUI();
    },

    initMychoiceUI: function() {
        $Mychoice.initUI();
    },

    initProfileUI: function() {
        $Profile.initUI();
    }
}