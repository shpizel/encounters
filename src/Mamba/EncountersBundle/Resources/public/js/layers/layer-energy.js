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
            $.post($Routing.getPath('battery.charge'), function($data) {
                if ($data.status == 0 && $data.message == "") {
                    $Battery.setCharge(5);
                    $Account.setAccount($data.data['account']);

                    $("div#overflow").hide();
                    $("div.app-layer").hide();
                } else if ($data.status == 3) {
                    $Layers.showAccountLayer({'status': $data.status});
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

        $("div.layer-energy").show();
    }
};