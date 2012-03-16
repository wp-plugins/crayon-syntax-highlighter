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