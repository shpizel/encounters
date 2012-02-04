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