if (typeof CrayonTagEditorSettings == 'undefined') {
	// WP may have already added it
	CrayonTagEditorSettings = {};
}

// Sets the TINYMCE_USED setting
CrayonTagEditorSettings.setUsed = function(is_used) {
	if (typeof this.ajax_url != 'undefined') {
		if (this.ajax_url && !this.used) {
			is_used = is_used ? '1' : '0';
			var used_url = this.ajax_url + '?' + this.used_setting + '=' + is_used;
			jQuery.get(used_url, function(data) {
			});
		}
	}
}

var CrayonTagEditor = new function() {
	
	// VE specific
	var loaded = false;
	var insertCallback;
	var editor_name;
	var ajax_class_timer = null;
	var ajax_class_timer_count = 0;
	
//	var br_before, br_after;
	
	// CSS
	var tag_editor, warning, code, clear;
	
    // Creates needed resources
    this.load = function() {    	
    	// Loaded once url is given
    	if (!loaded) {
    		loaded = true;
    	} else {
    		return;
    	}
    	
    	// Generated in WP and contains the settings
    	var settings = CrayonTagEditorSettings;
//    	br_before = settings.br_before ? '<p>&nbsp;</p>\n' : '';
//    	br_after = settings.br_after ? '\n<p>&nbsp;</p>\n' : '\n';
    	
        // Load the editor content 
        jQuery.get(settings.url, function(data) {
        	tag_editor = jQuery('<div id="'+CrayonTagEditorSettings.css+'"></div>');
            tag_editor.appendTo('body').hide();
        	tag_editor.html(data);
        	
        	code = jQuery(settings.css_code);
        	clear = jQuery('#crayon-te-clear');
        	code.change(function() {
        		if ( code.val().length > 0 ) {
        			clear.show();
        			warning.hide();
        		} else if ( clear.is(":visible") ) {
        			clear.hide();
        		}
        	});
        	clear.click(function() {
        		code.val('');
        		warning.hide();
        		code.focus();
        	});
        	
        	// Set up overides.
        	// When checkbox is selected, input element is enabled
        	jQuery('.crayon-te-check[data-id]').each(function() {
        		jQuery(this).change(function() {
        			var id = jQuery(this).attr('data-id');
        			var checked = jQuery(this).is(':checked');
        			var input = jQuery('.crayon-te-input[data-id='+id+']');
            		if (typeof input != 'undefined') {
            			// Toggle disable
            			input.prop('disabled', !checked);
            		}
        		});
        	});
        	
        	warning = jQuery('#crayon-te-warning');
        	warning.html('Please type some code first...');
        	
        	// Create the Crayon Tag
        	tag_editor.find('#crayon-te-submit').click(function() {
        		if (code.val().length == 0) {
        			warning.show();
        			code.focus();
        			return false;
        		} else {
        			warning.hide();
        		}
        		
//        		var br = editor_name == 'html' ? '\n' : '<p>&nbsp;</p>';
//        		var br_before = editor_name == 'html' && settings.br_before ? br : '';
//        		var br_after = settings.br_after ? br : '';
        		
        		var br_before = br_after = '';
        		if (editor_name == 'html') {
        			br_after = br_before = '\n'; 
        		} else {
        			br_after = '<p>&nbsp;</p>';
        		}
        		
    			var shortcode = br_before + '<pre ';
    			
    			// Add title if given
    			jQuery('.crayon-te-input[data-id="title"]').each(function() {
    				var value = jQuery(this).val();
    				if (value.length > 0) {
    					shortcode += 'title="' + value + '" ';
    				}
    			});
    			
    			var atts = {};
    			shortcode += 'class="'+settings.pre_css+' '; 
    			
    			// Always add language
    			jQuery('.crayon-te-input[data-id="lang"]').each(function() {
    				var value = jQuery(this).val();
    				atts['lang'] = value;
    			});
    			
    			// Grab overides
            	jQuery('.crayon-te-check[data-id]').each(function() {
            		var id = jQuery(this).attr('data-id');
            		var checked = jQuery(this).is(':checked');
            		if (checked) {
            			var input = jQuery('.crayon-te-input[data-id='+id+']');
                		if (typeof input != 'undefined') {
                			var value = input.val();
                			atts[id] = value;
                		}
            		}
            	});
    			
            	atts['decode'] = 'true';
    			for (var att in atts) {
    				shortcode += att + settings.attr_sep + atts[att] + ' ';
    			}
    			
    			var content = jQuery('#crayon-te-code');
    			content = typeof content != 'undefined' ? content.val() : ''; 
    			// Convert all non-whitespace <code>, including spaces
    			content = content.replace(/([^\t\r\n]+)/gm,'<code class="'+settings.code_css+'">$1</code>');
    			content = content.replace(/^([\s]*?)$/gm,'<code class="'+settings.code_css+'"></code>');
    			shortcode += '">' + content + '</pre>' + br_after;
    			
    			// Insert the tag and hide dialog
    			insertCallback(shortcode);
    			
    			tb_remove();
    			var ajax = jQuery('#TB_ajaxContent');
            	if ( typeof ajax == 'undefined' ) {
            		ajax.removeClass('crayon-te-ajax');
            	}
    		});
        });
    };
	
    // Displays the dialog
	this.dialog = function(callback, editor_str) {
    	tb_show('Add Crayon Code', '#TB_inline?inlineId=' + CrayonTagEditorSettings.css);
    	code.focus();
    	insertCallback = callback;
    	editor_name = editor_str;
    	if (ajax_class_timer) {
    		clearInterval(ajax_class_timer);
    		ajax_class_timer_count = 0;
    	}
    	
    	ajax_class_timer = setInterval(function () {
        	var ajax_window = jQuery('#TB_window');
        	if ( typeof ajax_window != 'undefined' && !ajax_window.hasClass('crayon-te-ajax') ) {
        		ajax_window.addClass('crayon-te-ajax');
        		clearInterval(ajax_class_timer);
        	}
        	if (ajax_class_timer_count >= 100) {
        		// In case it never loads, terminate
        		clearInterval(ajax_class_timer);
        	}
        	ajax_class_timer_count++;
    	}, 40);
    	
    	CrayonTagEditorSettings.setUsed(true);
    };
	
};
