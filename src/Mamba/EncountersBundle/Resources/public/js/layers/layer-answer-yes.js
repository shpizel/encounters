/**
 * Answer yes layer client scripts
 *
 * @author shpizel
 */

$Layers.$AnswerYesLayer = {

    /**
     * Init UI
     *
     * @init UI
     */
    initUI: function() {
        $("div.layer-yes a.ui-btn").click($Layers.openMessengerWindowFunction);
    },

    /**
     * Shows layer
     *
     * @show layer
     */
    showLayer: function($data) {
        if (!$data) {
            var currentQueueElement = $Search.$storage['currentQueueElement'];
            $("div.layer-yes div.content-center a").attr(
                {
                    'href': $Config.get('platform').partner_url + "app_platform/?action=view&app_id=" + $Config.get('platform').app_id + "&extra=profile" + currentQueueElement['info']['id'],
                    'target': '_top'
                }
            ).text(currentQueueElement['info']['name']);
            $("div.layer-yes div.center a.see").attr(
                {
                    'href': $Config.get('platform').partner_url + "app_platform/?action=view&app_id=" + $Config.get('platform').app_id + "&extra=profile" + currentQueueElement['info']['id'],
                    'target': '_top'
                }
            );
        } else {
            $Config.set('current_user_id', $data['user_id']);
            $("div.layer-yes div.content-center a").attr(
                {
                    'href': $Config.get('platform').partner_url + "app_platform/?action=view&app_id=" + $Config.get('platform').app_id + "&extra=profile" + $data['user_id'],
                    'target': '_top'
                }
            ).text($data['name']);
            $("div.layer-yes div.center a.see").attr(
                {
                    'href': $Config.get('platform').partner_url + "app_platform/?action=view&app_id=" + $Config.get('platform').app_id + "&extra=profile" + $data['user_id'],
                    'target': '_top'
                }
            );
        }

        /** Нужно заполнить user info block */
        var currentQueueElement = ($data) ? $Config.get('users')[$Config.get('current_user_id')] : $Search.$storage['currentQueueElement'];
        $("div.layer-yes div.face img").attr('src', currentQueueElement['info']['medium_photo_url']);
        $("div.layer-yes a.ui-btn").attr("user_id", currentQueueElement['info']['id']);
        $("div.layer-yes div.info div.name a").attr(
            {
                'href': $Config.get('platform').partner_url + "app_platform/?action=view&app_id=" + $Config.get('platform').app_id + "&extra=profile" + currentQueueElement['info']['id'],
                'target': '_top'
            }
        ).text(currentQueueElement['info']['name']);

        if (currentQueueElement['info']['gender'] == 'F') {
            $("div.layer-yes div.info div.name i").removeClass('male');
            $("div.layer-yes div.info div.name i").addClass('female');
        } else {
            $("div.layer-yes div.info div.name i").removeClass('female');
            $("div.layer-yes div.info div.name i").addClass('male');
        }

        $("div.layer-yes div.info div.location").html(currentQueueElement['info']['location']['country']['name'] + ", " + currentQueueElement['info']['location']['city']['name']);
        $("div.layer-yes div.info div.age").html((currentQueueElement['info']['age'] ? (currentQueueElement['info']['age'] + ', ' + currentQueueElement['info']/*['other']*/['sign']) : '&nbsp;'));
        $("div.layer-yes div.info div.lookingfor span").html(currentQueueElement['info']['familiarity']['lookfor']);

        $("div.layer-yes").show();
    }
};