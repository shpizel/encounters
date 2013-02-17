/**
 * Level layer client scripts
 *
 * @author shpizel
 */

$Layers.$LevelLayer = {

    /**
     * Init UI
     *
     * @init UI
     */
    initUI: function() {
        $("div.layer-level div._tell a.see").click(function() {
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

        $("div.layer-level p a.ui-btn").click(function() {
            $.post($Routing.getPath('level.up'), function($data) {
                if ($data.status == 0 && $data.message == "") {
                    var
                        $energy = $data.data['popularity']['energy'],
                        $next = $data.data['popularity']['next'],
                        $prev = $data.data['popularity']['prev'],
                        $level = $data.data['popularity']['level']
                        ;

                    $Config.$storage['webuser']['popularity'] = $data.data['popularity'];

                    $(".info-meet li.item-popularity div.bar div.level-background").attr('class', 'level-background lbc' + (parseInt(($energy - $prev)*100/($next - $prev)/25) + 1));
                    $(".info-meet li.item-popularity div.bar div.level").attr('class', 'level l' + $level);
                    $(".info-meet li.item-popularity div.bar div.speedo").css('width', parseInt(($energy - $prev)*100/($next - $prev)*0.99)+'px');

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
        var $popularity = $Config.get('webuser')['popularity'];
        $("div.layer-level div.level").attr("class", 'level l' + $popularity['level']);
        $("div.layer-level div.current").attr("class", 'current l' + $popularity['level']);
        $("div.layer-level div.next").attr("class", 'next l' + ($popularity['level'] < 16 ? ($popularity['level'] + 1) : $popularity['level']));
        $("div.layer-level div.speedo").css('width', parseInt(($popularity['energy'] - $popularity['prev'])*100/($popularity['next'] - $popularity['prev'])*1.99)+'px');

        if ($popularity['level'] < 16 && $popularity['level'] >= 4) {
            $("div.layer-level p a.ui-btn").html("Перейти на " + ($popularity['level']+1) + "-й уровень за " + 10*($popularity['level'] + 1 - 4) +"<i class=\"account-heart\"></i>");
            $("div.layer-level p a.ui-btn").attr('cost',  ($popularity['level'] + 1 - 4)*10);
            $("div.layer-level p a.ui-btn").attr('level',  $popularity['level'] + 1);
            $("div.layer-level p._buy").show();
            $("div.layer-level div._tell").hide();
        } else {
            $("div.layer-level div._tell").show();
            $("div.layer-level p._buy").hide();
        }

        $("div.layer-level").show();
    }
};