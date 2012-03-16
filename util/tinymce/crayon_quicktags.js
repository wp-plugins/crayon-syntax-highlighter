console.log('load quick');

var CrayonQuickTags = function() {

	// TODO need to load ve from url?
	
	var args = {
			url : 'http://localhost/crayon/wp-content/plugins/crayon-syntax-highlighter/util/tinymce',
//			used : crayon_used,
			br_before : true,
			br_after : true,
			br : '\n'
			};
	jQuery(function() {CrayonVisualEditor.load(args)});
	
	QTags.addButton( 'crayon_quicktag', 'Crayon', my_callback );
	function my_callback() {
		CrayonVisualEditor.settings(args);
		
		CrayonVisualEditor.dialog(function(shortcode) {
			QTags.insertContent(shortcode);
		});
	}

}

CrayonQuickTags();