/**
 * Not see yet layer client scripts
 *
 * @author shpizel
 */

$Layers.$AnswerNotSeeYetLayer = {

    /**
     * Init UI
     *
     * @init UI
     */
    initUI: function() {
        $("div.layer-not-see-yet div.content-center a.first").click(function() {
            var $userId = ($Search.$storage.hasOwnProperty('currentQueueElement') ? $Search.$storage['currentQueueElement']['info']['id'] : $Config.get('current_user_id'));
            $.post($Routing.getPath('queue.add'), {'user_id': $userId}, function($data) {
                if ($data.status == 0 && $data.message == "") {
                    $Account.setAccount($data.data['account']);

                    $("div#overflow").hide();
                    $("div.app-layer").hide();

                    alert('Операция прошла успешно!');
                } else if ($data.status == 3) {
                    $Layers.showAccountLayer({'status': $data.status});
                }
            });

            return false;
        });

        $("div.layer-not-see-yet a.ui-btn").click($Layers.openMessengerWindowFunction);
    },

    /**
     * Shows layer
     *
     * @show layer
     */
    showLayer: function($data) {
        if (!$data) {
            var currentQueueElement = $Search.$storage['currentQueueElement'];
            $("div.layer-not-see-yet div.content-center span.name").text(currentQueueElement['info']['name']);
            /*$("div.layer-not-see-yet div.photo img").attr('src', currentQueueElement['info']['medium_photo_url']);*/
        } else {
            $Config.set('current_user_id', $data['user_id']);
            $("div.layer-not-see-yet div.content-center span.name").text($data['name']);
            /*$("div.layer-not-see-yet div.photo img").attr('src', $data['medium_photo_url']);*/
        }

        /** Нужно заполнить user info block */
        var currentQueueElement = ($data) ? $Config.get('users')[$Config.get('current_user_id')] : $Search.$storage['currentQueueElement'];
        $("div.layer-not-see-yet div.face img").attr('src', currentQueueElement['info']['medium_photo_url']);
        $("div.layer-not-see-yet a.ui-btn").attr("user_id", currentQueueElement['info']['id']);
        $("div.layer-not-see-yet div.info div.name a").attr('href', /*$Config.get('platform').partner_url + "anketa.phtml?oid="*/ "/profile?id=" + currentQueueElement['info']['id']).text(currentQueueElement['info']['name']);

        if (currentQueueElement['info']['gender'] == 'F') {
            $("div.layer-not-see-yet div.info div.name i").removeClass('male');
            $("div.layer-not-see-yet div.info div.name i").addClass('female');
        } else {
            $("div.layer-not-see-yet div.info div.name i").removeClass('female');
            $("div.layer-not-see-yet div.info div.name i").addClass('male');
        }

        $("div.layer-not-see-yet div.info div.location").html(currentQueueElement['info']['location']['country'] + ", " + currentQueueElement['info']['location']['city']);
        $("div.layer-not-see-yet div.info div.age").html((currentQueueElement['info']['age'] ? (currentQueueElement['info']['age'] + ', ' + currentQueueElement['info']/*['other']*/['sign']) : '&nbsp;'));
        $("div.layer-not-see-yet div.info div.lookingfor span").html(currentQueueElement['info']['familiarity']['lookfor']);

        $("div.layer-not-see-yet").show();
    }
};