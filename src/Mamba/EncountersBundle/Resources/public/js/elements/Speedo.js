/**
 * Speedo
 *
 * @author shpizel
 */
$Speedo = {

    /**
     * Инициализация интерфейса элемента
     *
     * @init UI
     */
    initUI: function($route) {
        $("li.item-popul").click(function() {
            $Layers.showPopularityLayer();
        });
    }
}