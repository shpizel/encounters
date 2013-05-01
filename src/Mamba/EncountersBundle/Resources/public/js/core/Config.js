/**
 * Config
 *
 * @package js
 */
$Config = {

    /**
     * Storage
     *
     * @var object
     */
    $storage: {},

    /**
     * Setter
     *
     *
     */
    set: function() {
        var $argc = arguments.length;
        if ($argc == 2) {
            this.$storage[arguments[0]] = arguments[1];
            return arguments[1];
        } else if ($argc == 1 && typeof(arguments[0]) == 'object') {
            for (var $key in arguments[0]) {
                this.set($key, arguments[0][$key]);
            }
            return arguments[0];
        }
    },

    /**
     * Getter
     *
     * @return mixed
     */
    get: function() {
        return this.$storage[arguments[0]];
    }
}