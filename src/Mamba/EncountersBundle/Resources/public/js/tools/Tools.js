/**
 * Tools
 *
 * @author shpizel
 */
$Tools = {

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
    microtime: function($float) {
        var
            now = new Date().getTime() / 1000,
            s = parseInt(now, 10)
        ;

        return ($float) ? now : /*(Math.round((now - s) * 1000) / 1000) + ' ' + s*/ $Tools.round(now, 0);
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
            r = $Tools.rand(0, l);
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
    },

    /**
     * Get cookie
     *
     * @param $name
     * @return {String}
     */
    getCookie: function($name) {
        var matches = document.cookie.match(new RegExp(
            "(?:^|; )" + $name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
        ));

        return matches ? decodeURIComponent(matches[1]) : undefined
    },

    /**
     * Set cookie
     *
     * @param $name
     * @param $value
     * @param $props
     */
    setCookie: function($name, $value, $props) {
        $props = $props || {}
        var exp = $props.expires
        if (typeof exp == "number" && exp) {
            var d = new Date()
            d.setTime(d.getTime() + exp*1000)
            exp = $props.expires = d
        }
        if(exp && exp.toUTCString) { $props.expires = exp.toUTCString() }

        $value = encodeURIComponent($value)
        var updatedCookie = $name + "=" + $value
        for(var propName in $props){
            updatedCookie += "; " + propName
            var propValue = $props[propName]
            if(propValue !== true){ updatedCookie += "=" + propValue }
        }
        document.cookie = updatedCookie
    },

    /**
     * Remove cookies
     *
     * @param $name
     */
    deleteCookie:function($name) {
        setCookie($name, null, { expires: -1 })
    }
}