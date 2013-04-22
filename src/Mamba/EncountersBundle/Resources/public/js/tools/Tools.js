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
    },

    /**
     * Logger
     *
     * @params arg1, arg2 ... argN
     */
    log: function($arguments) {
        if ($Config.get('debug')) {
            if (window.console && window.console.log) {
                var $arguments  = Array.prototype.slice.call(arguments);
                $arguments.unshift('['+ $Tools.round($Tools.microtime(true) - $Config.get('domready_microtime'), 2) + ']');
                console.log.apply(console, $arguments);
            }
        }
    },

    ajaxPost: function($method, $postData, $doneCallback, $failCallback, $alwaysCallback) {
        $.post($Routing.getPath($method), $postData || {})
            .done(function($data) {
                $doneCallback && $doneCallback($data);

                if ($data.status == 0 && $data.message == '') {
                    $Tools.log('method: ' + $method + ', post data:', $.toJSON($postData) + ', result:', $data.data, ', generation time:', $Tools.round($data.metrics.generation_time, 2)*1000 + 'ms');
                }  else {
                    $Tools.log('method: ' + $method + ', post data:' + $.toJSON($postData) +', result:' + $.toJSON({'code': $data.status, 'message': $data.message}) + ', generation time:', $Tools.round($data.metrics.generation_time, 2)*1000 + 'ms');
                }
            })
            .fail(function() {
                $failCallback && $failCallback();

                $Tools.log('method: ' + $method + ', post data: ' + $.toJSON($postData) + ', result: FAILED');
            })
            .always(function() {
                $alwaysCallback && $alwaysCallback();
            })
        ;
    },

    saveSelection: function() {
        if (window.getSelection) {
            var sel = window.getSelection();
            if (sel.getRangeAt && sel.rangeCount) {
                $Config.set('window.selection', sel.getRangeAt(0));
            }
        } else if (document.selection && document.selection.createRange) {
            $Config.set('window.selection', document.selection.createRange());
        } else {
            $Config.set('window.selection', null);
        }
    },

    restoreSelection: function() {
        var range = $Config.get('window.selection');
        if (range) {
            if (window.getSelection) {
                sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(range);
            } else if (document.selection && range.select) {
                range.select();
            }
        }

    }
}