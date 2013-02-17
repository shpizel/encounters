/**
 * Level achievements layer client scripts
 *
 * @author shpizel
 */

$Layers.$LevelAchievementLayer = {

    /**
     * Init UI
     *
     * @init UI
     */
    initUI: function() {
        $("div.layer-level-achievement div._tell a.see").click(function() {
            if ($Config.get('non_app_users_contacts').length) {
                var $ids = $Config.get('non_app_users_contacts');
                $ids = $Tools.shuffle($ids);
                $ids = $ids.slice(0, 9);

                var text = "Привет! Установи приложение «Выбиратор», в нем очень удобно смотреть анкеты, плюс — твои фотографии тоже очень быстро получат множество просмотров и оценок ;-)";
                mamba.method('message', text, '', $ids);
                $Config.set('message-text', text);
                $Config.set('message-ids', $ids);

            }
            $("div#overflow").hide();
            $("div.app-layer").hide();

            return false;
        });
    },

    /**
     * Shows layer
     *
     * @show layer
     */
    showLayer: function($data) {
        var $level = $Config.get('webuser')['popularity']['level'];
        $("div.layer-level-achievement div.level").attr("class", 'level l' + $level);

        $("div.layer-level-achievement").show();
    }
};