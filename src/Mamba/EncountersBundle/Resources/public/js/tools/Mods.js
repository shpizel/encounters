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

if(!Object.keys) Object.keys = function(o){
    if (o !== Object(o))
        throw new TypeError('Object.keys called on non-object');
    var ret=[], p;
    for(p in o) if(Object.prototype.hasOwnProperty.call(o,p)) ret.push(p);
    return ret;
}

jQuery.extend({
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
    }
});

jQuery.extend( {
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
});