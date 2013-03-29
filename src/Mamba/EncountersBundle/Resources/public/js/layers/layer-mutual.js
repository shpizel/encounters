/**
 * Mutual layer client scripts
 *
 * @author shpizel
 */

$Layers.$MutualLayer = {

    /**
     * Init UI
     *
     * @init UI
     */
    initUI: function() {

    },

    /**
     * Shows layer
     *
     * @show layer
     */
    showLayer: function($data) {
        if (!$data) {
            var currentQueueElement = $Search.$storage['currentQueueElement'];
            $("div.layer-mutual div.photo div.current").css('background', "url('" + currentQueueElement['info']['medium_photo_url'] + "')");
            $("div.layer-mutual div.content-center a").attr('href', /*$Config.get('platform').partner_url + "anketa.phtml?oid="*/ "/profile?id=" + currentQueueElement['info']['id']).text(currentQueueElement['info']['name']);
            $("div.layer-mutual div.center a.see").attr('href', /*$Config.get('platform').partner_url + "anketa.phtml?oid="*/ "/profile?id=" + currentQueueElement['info']['id']);
        } else {
            $("div.layer-mutual div.photo div.current").css('background', "url('" + $data['medium_photo_url'] + "')");
            $("div.layer-mutual div.content-center a").attr('href', /*$Config.get('platform').partner_url + "anketa.phtml?oid="*/ "/profile?id=" + $data['user_id']).text($data['name']);
            $("div.layer-mutual div.center a.see").attr('href', /*$Config.get('platform').partner_url + "anketa.phtml?oid="*/ "/profile?id=" + $data['user_id']);
        }

        $("div.layer-mutual div.photo div.web").css('background', "url('" + (($Config.get('webuser')['anketa']['info'].hasOwnProperty('medium_photo_url') && $Config.get('webuser')['anketa']['info']['medium_photo_url']) ? $Config.get('webuser')['anketa']['info']['medium_photo_url'] : '/bundles/encounters/images/photo_big_na.gif') + "')");
        $("div.layer-mutual").show();
    }
};