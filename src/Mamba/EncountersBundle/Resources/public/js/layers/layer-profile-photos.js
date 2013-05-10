/**
 * Profile photos layer
 *
 * @author shpizel
 */

$Layers.$ProfilePhotosLayer = {

    /**
     * Init UI
     *
     * @init UI
     */
    initUI: function() {
        $Config.set('photo-index', 0);

        $("div.layer-profile-photos div.photo-image_in").click(function() {
            var
                $photoIndex = $Config.get('photo-index'),
                $photos = $Config.get('photos')
            ;

            if ($photoIndex + 1 >= $photos.length) {
                $photoIndex = 0;
            } else {
                $photoIndex++;
            }

            $Config.set('photo-index', $photoIndex);

            var $Layer = $("div.layer-profile-photos");
            $("img", $Layer).attr('src', $Config.get('photos')[$Config.get('photo-index')]['huge_photo_url']);
            $("div.photo-col", $Layer).html("Фото " + ($Config.get('photo-index') + 1) + " из " + $Config.get('photos').length);
        });
    },

    /**
     * Shows layer
     *
     * @show layer
     */
    showLayer: function($data) {
        $("div.app-layer").addClass('app-layer-profile-photolayer');

        var $Layer = $("div.layer-profile-photos");
        $("img", $Layer).attr('src', $Config.get('photos')[$Config.get('photo-index')]['huge_photo_url']);
        $("div.photo-col", $Layer).html("Фото " + ($Config.get('photo-index') + 1) + " из " + $Config.get('photos').length);

        $Layer.show();
    },

    onClose: function() {
        $("div.app-layer").removeClass('app-layer-profile-photolayer');
    }
};