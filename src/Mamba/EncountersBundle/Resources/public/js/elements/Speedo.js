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
        $(".app-meet-button div.item-popularity div.bar div.speedo").click(function() {
            $Layers.showLevelLayer();
            return false;
        });

        $(".app-meet-button div.item-popularity div.bar div.speedo-background").click(function() {
            $Layers.showLevelLayer();
            return false;
        });

        $(".app-meet-button div.item-popularity div.bar div.level").click(function() {
            $Layers.showLevelLayer();
            return false;
        });

        $(".app-meet-button div.item-popularity div.bar div.level-background").click(function() {
            $Layers.showLevelLayer();
            return false;
        });
    }
}