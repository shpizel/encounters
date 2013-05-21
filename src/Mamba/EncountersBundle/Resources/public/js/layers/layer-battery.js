/**
 * Battery layer client scripts
 *
 * @author shpizel
 */

$Layers.$BatteryLayer = {

    /**
     * Init UI
     *
     * @init UI
     */
    initUI: function() {
        $("div.layer-battery form p a.ui-btn").click(function() {
            $Tools.ajaxPost('battery.charge', {}, function($data) {
                if ($data.status == 0 && $data.message == "") {
                    $Battery.setCharge(5);
                    $Account.setAccount($data.data['account']);

                    $("div#overflow").hide();
                    $("div.app-layer").hide();
                } else if ($data.status == 3) {
                    //$Layers.showAccountLayer({'status': $data.status});
                    var $extra = {service: {id: 1}};
                    mamba.method('pay', 1, $.toJSON($extra));
                    location.href = $Routing.getPath("billing");
                }
            });

            return false;
        });
    },

    /**
     * Shows layer
     *
     * @show layer
     */
    showLayer: function($data) {
        var $charge = $Battery.getCharge();

        if ($charge == 0) {
            $("div.layer-battery div.battery-big").attr('class', 'battery-big empty');
            $("div.layer-battery .title").attr('class', 'title');

            var $account = $Account.getAccount();
            var $heartsNeeded = (5 - $Battery.getCharge())*2;
            if ($account >= $heartsNeeded) {
                $("div.layer-battery p.center a.ui-btn").html("Получить 100% энергии за " + $heartsNeeded + "<i class=\"account-heart\"></i>");
            } else {
                $("div.layer-battery p.center a.ui-btn").html("Получить 100% энергии за 1<i class=\"coint\"></i>");
            }

            $("div.layer-battery p.center a.close").hide();
            $("div.layer-battery p.center a.ui-btn").show();
        } else if ($charge >= 0 && $charge < 5 ) {
            $("div.layer-battery div.battery-big").attr('class', 'battery-big middle');
            $("div.layer-battery .title").attr('class', 'title');

            /** на потом */
            $("div.layer-battery p.center a.ui-btn").html("Пополнить до 100% за " + (5 - $Battery.getCharge())*2 + "<i class=\"account-heart\"></i>");

            $("div.layer-battery p.center a.close").hide();
            $("div.layer-battery p.center a.ui-btn").show();
        } else {
            $("div.layer-battery div.battery-big").attr('class', 'battery-big full');
            $("div.layer-battery .title").attr('class', 'title charged');
            $("div.layer-battery p.center a.ui-btn").hide();
            $("div.layer-battery p.center a.close").show();
        }

        $("div.layer-battery").show();
    }
};