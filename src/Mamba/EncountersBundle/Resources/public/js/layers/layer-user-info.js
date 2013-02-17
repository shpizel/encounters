/**
 * User info layer client scripts
 *
 * @author shpizel
 */

$Layers.$UserInfoLayer = {

    /**
     * Init UI
     *
     * @init UI
     */
    initUI: function() {
        $("div.layer-user-info a.ui-btn").click($Layers.openMessengerWindowFunction);
    },

    /**
     * Shows layer
     *
     * @show layer
     */
    showLayer: function($data) {
        var currentQueueElement = ($data) ? $data : $Search.$storage['currentQueueElement'];
        $("div.layer-user-info div.face img").attr('src', currentQueueElement['info']['medium_photo_url']);
        $("div.layer-user-info a.ui-btn").attr("user_id", currentQueueElement['info']['id']);
        $("div.layer-user-info div.info div.name a").attr('href', $Config.get('platform').partner_url + "anketa.phtml?oid=" + currentQueueElement['info']['id']).text(currentQueueElement['info']['name']);

        if (currentQueueElement['info']['gender'] == 'F') {
            $("div.layer-user-info div.info div.name i").removeClass('male');
            $("div.layer-user-info div.info div.name i").addClass('female');
        } else {
            $("div.layer-user-info div.info div.name i").removeClass('female');
            $("div.layer-user-info div.info div.name i").addClass('male');
        }

        $("div.layer-user-info div.info div.location").html(currentQueueElement['info']['location']['country'] + ", " + currentQueueElement['info']['location']['city']);
        $("div.layer-user-info div.info div.age").html((currentQueueElement['info']['age'] ? (currentQueueElement['info']['age'] + ', ' + currentQueueElement['info']/*['other']*/['sign']) : '&nbsp;'));
        $("div.layer-user-info div.info div.lookingfor span").html(currentQueueElement['info']['familiarity']['lookfor']);

        $("div.layer-user-info").show();
    }
};