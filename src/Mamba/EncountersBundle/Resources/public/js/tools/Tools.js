/**
 * Tools
 *
 * @author shpizel
 */
Tools = {

    /**
     * Делает первый символ строки заглавным
     *
     * @param string $str
     * @return string
     */
    ucfirst: function($str) {
        var $firstChar = $str.charAt(0).toUpperCase();
        return $firstChar + $str.substr(1, $str.length - 1);
    },

    /**
     * Microtime getter
     *
     * @return float
     */
    microtime: function() {
        var now = new Date().getTime() / 1000, s = parseInt(now, 10), get_as_float = true;
        return (get_as_float) ? now : (Math.round((now - s) * 1000) / 1000) + ' ' + s;
    },

    /**
     * Array shufler
     *
     * @param $array
     * @return array
     */
    shuffle: function($array) {
        var l = $array.length, t, r;
        while(--l > 0) {
            r = Tools.rand(0, l);
            if(r != l) {
                t = $array[l];
                $array[l] = $array[r];
                $array[r] = t;
            }
        }
        return $array;
    },

    /**
     * Random getter
     *
     * @param int $from
     * @param int $to
     *
     * @return int
     */
    rand: function($from, $to) {
        return Math.floor(Math.random() * ($to - $from + 1)) + $from;
    },

    /**
     * Round
     *
     * @param float value
     * @param int precision
     */
    round: function($value, $precision) {
        var factor = 1;
        for (var i=0; i<$precision; i++) {
            factor*=10;
        }
        return Math.round(factor*$value)/factor;
    }
}