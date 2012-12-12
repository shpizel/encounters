/**
 * Battery
 *
 * @author shpizel
 */
$Battery = {

    /**
     * Инициализация интерфейса
     *
     * @init UI
     */
    initUI: function($route) {
        $("li.item-energy > *").click(function() {
            $Layers.showBatteryLayer();
            return false;
        });
    },

    /**
     * Устанавливает заряд батарейки
     *
     * @param $charge
     */
    setCharge: function($charge) {
        $("b.battery b").css({'width': $charge*15 + "%"});
        //$("li.item-energy i").html($charge*20 + "%");

        var $webUser = $Config.get('webuser');
        if ($webUser && $webUser.hasOwnProperty('battery')) {
            $webUser['battery'] = $charge;
        }
    },

    /**
     * Возвращает текущий заряд
     *
     * @return int
     */
    getCharge: function() {
        var $webUser = $Config.get('webuser');
        if ($webUser && $webUser.hasOwnProperty('battery')) {
            return $webUser['battery'];
        }

        return 0;
    }
}