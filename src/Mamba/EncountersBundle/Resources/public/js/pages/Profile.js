/**
 * Profile
 *
 * @author shpizel
 */
$Profile = {

    /**
     * Инициализация интерфейса
     *
     * @init
     */
    initUI: function() {

        /**
         * 1) клик по фотке
         * 2) клик по кнопке "отправить подарок"
         * 3) наведение курсора на гифты
         * 4)
         *
         * @author shpizel
         */

        $(document).click(function(){
            $(".orange-menu .drop-down").hide();
        });

        $(".orange-menu .item-arrow").click(function() {
            $(".orange-menu .drop-down").fadeIn('fast');
            return false;
        });

        $(".profile-picture").click(function() {
            $Layers.showProfilePhotosLayer();
        });

        $(".button-present").click(function() {
            $Layers.showSendGiftLayer();
        });

        $(".orange-menu .message").click(function() {
            $Layers.openMessengerWindowFunction();
        });
    },

    /**
     * Запуск страницы
     *
     * @run page
     */
    run: function() {

    }
}