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
        $("li.item-energy > *").click(function() {
            alert('Я батарейка!');
        });
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