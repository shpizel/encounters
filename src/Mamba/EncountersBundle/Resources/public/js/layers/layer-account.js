/**
 * Account layer client scripts
 *
 * @author shpizel
 */

$Layers.$AccountLayer = {

    /**
     * Init UI
     *
     * @init UI
     */
    initUI: function() {
        $("div.layer-account div.row div.coins a.ui-btn").click(function() {
            var
                $hearts = $(this).attr('hearts'),
                $coins = $(this).attr('coins')
            ;

            var $extra = {service: {id: 4, coins: $coins, hearts: $hearts}};
            mamba.method('pay', $coins, $.toJSON($extra));
            location.href = $Routing.getPath("billing");
        });
    },

    /**
     * Shows layer
     *
     * @show layer
     */
    showLayer: function($data) {
        if (3 == $data && $data['status']) {
            $("div.layer-account div.title").addClass("not-enough");
        } else {
            $("div.layer-account div.title").removeClass("not-enough");
        }

        $("div.layer-account").show();
    }
};