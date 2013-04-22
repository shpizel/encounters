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