/**
 * Answer maybe layer client scripts
 *
 * @author shpizel
 */

$Layers.$AnswerMaybeLayer = {

    /**
     * Init UI
     *
     * @init UI
     */
    initUI: function() {
        $("div.layer-maybe a.ui-btn").click($Layers.openMessengerWindowFunction);
    },

    /**
     * Shows layer
     *
     * @show layer
     */
    showLayer: function($data) {
        if (!$data) {
            var currentQueueElement = $Search.$storage['currentQueueElement'];
            $("div.layer-maybe div.content-center a").attr('href', /*$Config.get('platform').partner_url + "anketa.phtml?oid="*/ "/profile?id=" + currentQueueElement['info']['id']).text(currentQueueElement['info']['name']);
            $("div.layer-maybe div.center a.see").attr('href', /*$Config.get('platform').partner_url + "anketa.phtml?oid="*/ "/profile?id=" + currentQueueElement['info']['id']);
        } else {
            $Config.set('current_user_id', $data['user_id']);
            /*$("div.layer-maybe div.photo img").attr('src', $data['medium_photo_url']);*/
            $("div.layer-maybe div.content-center a").attr('href', /*$Config.get('platform').partner_url + "anketa.phtml?oid="*/ "/profile?id=" + $data['user_id']).text($data['name']);
            $("div.layer-maybe div.center a.see").attr('href', /*$Config.get('platform').partner_url + "anketa.phtml?oid="*/ "/profile?id=" + $data['user_id']);
        }

        /** Нужно заполнить user info block */
        var currentQueueElement = ($data) ? $Config.get('users')[$Config.get('current_user_id')] : $Search.$storage['currentQueueElement'];
        $("div.layer-maybe div.face img").attr('src', currentQueueElement['info']['medium_photo_url']);
        $("div.layer-maybe a.ui-btn").attr("user_id", currentQueueElement['info']['id']);
        $("div.layer-maybe div.info div.name a").attr('href', /*$Config.get('platform').partner_url + "anketa.phtml?oid="*/ "/profile?id=" + currentQueueElement['info']['id']).text(currentQueueElement['info']['name']);

        if (currentQueueElement['info']['gender'] == 'F') {
            $("div.layer-maybe div.info div.name i").removeClass('male');
            $("div.layer-maybe div.info div.name i").addClass('female');
        } else {
            $("div.layer-maybe div.info div.name i").removeClass('female');
            $("div.layer-maybe div.info div.name i").addClass('male');
        }

        $("div.layer-maybe div.info div.location").html(currentQueueElement['info']['location']['country'] + ", " + currentQueueElement['info']['location']['city']);
        $("div.layer-maybe div.info div.age").html((currentQueueElement['info']['age'] ? (currentQueueElement['info']['age'] + ', ' + currentQueueElement['info']/*['other']*/['sign']) : '&nbsp;'));
        $("div.layer-maybe div.info div.lookingfor span").html(currentQueueElement['info']['familiarity']['lookfor']);

        $("div.layer-maybe").show();
    }
};