/**
 * Energy layer client scripts
 *
 * @author shpizel
 */

$Layers.$EnergyLayer = {

    /**
     * Init UI
     *
     * @init UI
     */
    initUI: function() {
        $("div.layer-energy form p a").click(function() {
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
        if (!$data) {
            var currentQueueElement = $Search.$storage['currentQueueElement'];
            $("div.layer-energy div.info-block img").attr('src', currentQueueElement['info']['small_photo_url']);
            $("div.layer-energy span.name").text(currentQueueElement['info']['name']);
        } else {
            $("div.layer-energy div.info-block img").attr('src', $data['small_photo_url']);
            $("div.layer-energy span.name").text($data['name']);
        }

        var $account = $Account.getAccount();
        if ($account >= 10) {
            $("div.layer-energy form p a").html("Получить 100% энергии за 10<i class=\"account-heart\"></i>");
        } else {
            $("div.layer-energy form p a").html("Получить 100% энергии за 1<i class=\"coint\"></i>");
        }

        $("div.layer-energy").show();
    }
};