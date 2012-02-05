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
    initUI: function() {

    },

    /**
     * Устанавливает заряд батарейки
     *
     * @param $charge
     */
    setCharge: function($charge) {
        $("b.battery b").css({'width': $charge*15 + "%"});
        $("li.item-energy i").html($charge*20 + "%");
    }
}