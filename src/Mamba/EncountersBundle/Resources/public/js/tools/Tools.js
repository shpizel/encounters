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
    }
}