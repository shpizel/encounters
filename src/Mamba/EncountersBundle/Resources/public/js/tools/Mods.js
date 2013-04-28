/**
 * Моды
 *
 * @author shpizel
 */
Array.prototype.in_array = function($needle) {
    for(var i = 0, l = this.length; i < l; i++)	{
        if(this[i] == $needle) {
            return true;
        }
    }

    return false;
}

if(!Object.keys) {
    Object.keys = function(o){
        if (o !== Object(o))
            throw new TypeError('Object.keys called on non-object');
        var ret=[], p;
        for(p in o) if(Object.prototype.hasOwnProperty.call(o,p)) ret.push(p);
        return ret;
    }
}

/**
 * console.log.apply fix for IE
 *
 * @link https://gist.github.com/subzey/1466437
 **/
$.browser.msie &&
    (function(){var a=this.console,b=a&&a.log,c=!b||b.call?0:a.log=function(){c.apply.call(b,a,arguments)}})();
