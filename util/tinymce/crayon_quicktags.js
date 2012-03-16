console.log('load quick');

var CrayonQuickTags = function() {
	jQuery(function() {CrayonTagEditor.load()});
	
	QTags.addButton( 'crayon_quicktag', 'crayon', function() {
		CrayonTagEditor.dialog(function(shortcode) {
			QTags.insertContent(shortcode);
		}, 'html');
	} );
}

CrayonQuickTags();