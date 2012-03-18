RegExp.prototype.execAll = function(string) {
	var matches = [];
	var match = null;
	while ( (match = this.exec(string)) != null ) {
		var matchArray = [];
		for (var i in match) {
			if (parseInt(i) == i) {
				matchArray.push(match[i]);
			}
		}
		matches.push(matchArray);
	}
	return matches;
}

var CRAYON_DEBUG = true;

function console_log(string) {
    if (typeof console != 'undefined' && CRAYON_DEBUG) {
        console.log(string);
    }
}

//# is left unencoded
function crayon_escape(string) {
    if (typeof encodeURIComponent == 'function') {
    	return encodeURIComponent(string);
    } else if (typeof escape != 'function') {
    	return escape(string);
    } else {
    	return string;
    }
}
