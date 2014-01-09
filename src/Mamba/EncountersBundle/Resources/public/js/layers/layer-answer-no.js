/**
 * Answer no layer client scripts
 *
 * @author shpizel
 */

$Layers.$AnswerNoLayer = {

    /**
     * Init UI
     *
     * @init UI
     */
    initUI: function() {
        $("div.layer-no a.ui-btn").click($Layers.openMessengerWindowFunction);
    },

    /**
     * Shows layer
     *
     * @show layer
     */
    showLayer: function($data) {
        if (!$data) {
            var currentQueueElement = $Search.$storage['currentQueueElement'];
            /*$("div.layer-no div.photo img").attr('src', currentQueueElement['info']['medium_photo_url']);*/
            $("div.layer-no div.content-center span.name").text(currentQueueElement['info']['name']);
        } else {
            $Config.set('current_user_id', $data['user_id']);
            /*$("div.layer-no div.photo img").attr('src', $data['medium_photo_url']);*/
            $("div.layer-no div.content-center span.name").text($data['name']);
        }

        /** Нужно заполнить user info block */
        var currentQueueElement = ($data) ? $Config.get('users')[$Config.get('current_user_id')] : $Search.$storage['currentQueueElement'];
        $("div.layer-no div.face img").attr('src', currentQueueElement['info']['medium_photo_url']);
        $("div.layer-no a.ui-btn").attr("user_id", currentQueueElement['info']['id']);
        $("div.layer-no div.info div.name a").attr(
            {
                'href': $Config.get('platform').partner_url + "app_platform/?action=view&app_id=" + $Config.get('platform').app_id + "&extra=profile" + currentQueueElement['info']['id'],
                'target': '_top'
            }
        ).text(currentQueueElement['info']['name']);

        if (currentQueueElement['info']['gender'] == 'F') {
            $("div.layer-no div.info div.name i").removeClass('male');
            $("div.layer-no div.info div.name i").addClass('female');
        } else {
            $("div.layer-no div.info div.name i").removeClass('female');
            $("div.layer-no div.info div.name i").addClass('male');
        }

        $("div.layer-no div.info div.location").html(currentQueueElement['info']['location']['country']['name'] + ", " + currentQueueElement['info']['location']['city']['name']);
        $("div.layer-no div.info div.age").html((currentQueueElement['info']['age'] ? (currentQueueElement['info']['age'] + ', ' + currentQueueElement['info']/*['other']*/['sign']) : '&nbsp;'));
        $("div.layer-no div.info div.lookingfor span").html(currentQueueElement['info']['familiarity']['lookfor']);

        $("div.layer-no").show();
    }
};