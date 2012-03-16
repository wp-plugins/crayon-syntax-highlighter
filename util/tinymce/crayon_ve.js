
console.log('load ve');

// TODO rename all the stuff here to crayon_ve, which tiny_mce will use

var CrayonVisualEditor = new function() {
	
	// VE specific
	var loaded = false;
	var insertCallback;
	var ve_url = '/crayon_tinymce.php';
	var css = 'crayon-tinymce';
	var crayon_editor = null;
	var ajax_class_timer = null;
	var ajax_class_timer_count = 0;
	
	// TODO
	var settings_, br_before, br_after;
	var crayon_code, crayon_clear, crayon_tinymce_warning;
    
	this.settings = function(settings) {
		// If undefined, consider it used
		settings.used = (typeof settings.used == 'undefined') ? true : settings.used;
		br_before = settings.br_before ? settings.br : '';
		br_after = settings.br_after ? settings.br : '';
		settings_ = settings;
	}
	
    // Creates needed resources
    this.load = function(args) {
    	CrayonVisualEditor.settings(args);
    	
    	// If url not given, seek it
    	if (typeof settings_.url == 'undefined') {
    		console.log('no url');
    		return;
    		// TODO
    	} else {
    		console.log('url ' + settings_.url);
    	}
    	
    	// Loaded once url is given
    	if (!loaded) {
    		loaded = true;
    	} else {
    		return;
    	}
    	
        // Load the editor content 
        jQuery.get(settings_.url + ve_url + '?url=' + settings_.url, function(data) {
        	crayon_editor = jQuery('<div id="'+css+'"></div>');
            crayon_editor.appendTo('body').hide();
        	crayon_editor.html(data);
        	
        	// Highlight button
        	if (!settings_.used) {
        		jQuery('#content_crayon_tinymce').addClass('mce_crayon_tinymce_highlight');
        	}
        	
        	crayon_code = jQuery('#crayon-tinymce-code');
        	crayon_clear = jQuery('#crayon-tinymce-clear');
        	crayon_code.change(function() {
        		if ( crayon_code.val().length > 0 ) {
        			crayon_clear.show();
        			crayon_tinymce_warning.hide();
        		} else if ( crayon_clear.is(":visible") ) {
        			crayon_clear.hide();
        		}
        	});
        	crayon_clear.click(function() {
        		crayon_code.val('');
        		crayon_tinymce_warning.hide();
        		crayon_code.focus();
        	});
        	
        	// Set up overides.
        	// When checkbox is selected, input element is enabled
        	jQuery('.crayon-tinymce-check[data-id]').each(function() {
        		jQuery(this).change(function() {
        			var id = jQuery(this).attr('data-id');
        			var checked = jQuery(this).is(':checked');
        			var input = jQuery('.crayon-tinymce-input[data-id='+id+']');
            		if (typeof input != 'undefined') {
            			// Toggle disable
            			input.prop('disabled', !checked);
            		}
        		});
        	});
        	
        	crayon_tinymce_warning = jQuery('#crayon-tinymce-warning');
        	crayon_tinymce_warning.html('Please type some code first...');
        	
        	// Create the Crayon Tag
        	crayon_editor.find('#crayon-tinymce-submit').click(function() {
        		if (crayon_code.val().length == 0) {
        			crayon_tinymce_warning.show();
        			crayon_code.focus();
        			return false;
        		} else {
        			crayon_tinymce_warning.hide();
        		}
    			var shortcode = br_before + '<pre ';
    			
    			// Add title if given
    			jQuery('.crayon-tinymce-input[data-id="title"]').each(function() {
    				var value = jQuery(this).val();
    				if (value.length > 0) {
    					shortcode += 'title="' + value + '" ';
    				}
    			});
    			
    			var atts = {};
    			shortcode += 'class="crayon-syntax-pre '; 
    			
    			// Always add language
    			jQuery('.crayon-tinymce-input[data-id="lang"]').each(function() {
    				var value = jQuery(this).val();
    				atts['lang'] = value;
    			});
    			
    			// Grab overides
            	jQuery('.crayon-tinymce-check[data-id]').each(function() {
            		var id = jQuery(this).attr('data-id');
            		var checked = jQuery(this).is(':checked');
            		if (checked) {
            			var input = jQuery('.crayon-tinymce-input[data-id='+id+']');
                		if (typeof input != 'undefined') {
                			var value = input.val();
                			atts[id] = value;
                		}
            		}
            	});
    			
    			for (var att in atts) {
    				shortcode += att + '_' + atts[att] + ' ';
    			}
    			
    			var content = jQuery('#crayon-tinymce-code');
    			content = typeof content != 'undefined' ? content.val() : ''; 
    			// Convert all non-whitespace <code>, including spaces
    			content = content.replace(/([^\t\r\n]+)/gm,'<code class="crayon-code-line">$1</code>');
    			content = content.replace(/^([\s]*?)$/gm,'<code class="crayon-code-line"></code>');
    			shortcode += ' decode-true">\n' + content + '\n</pre>' + br_after;
    			
    			// Insert the tag and hide dialog
    			insertCallback(shortcode);
    			
    			tb_remove();
    			var ajax = jQuery('#TB_ajaxContent');
            	if ( typeof ajax == 'undefined' ) {
            		ajax.removeClass('crayon-tinymce-ajax');
            	}
    		});
        });
    };
	
    // Displays the dialog
	this.dialog = function(callback) {
    	// TODO calc win size to make
    	tb_show('Add Crayon Code', '#TB_inline?inlineId=' + css);
    	
    	crayon_code.focus();
    	
    	insertCallback = callback;
    	
    	if (ajax_class_timer) {
    		clearInterval(ajax_class_timer);
    		ajax_class_timer_count = 0;
    	}
    	
    	ajax_class_timer = setInterval(function () {
    		// TODO delay this, or put on timer?
        	var ajax = jQuery('#TB_ajaxContent');
        	var ajax_window = jQuery('#TB_window');
        	var ajax_title = jQuery('#TB_title');
        	if ( typeof ajax != 'undefined' && !ajax.hasClass('crayon-tinymce-ajax') ) {
        		ajax.addClass('crayon-tinymce-ajax');
        		ajax.height(ajax_window.height()-ajax_title.height());
        		clearInterval(ajax_class_timer);
        	}
        	if (ajax_class_timer_count >= 100) {
        		// In case it never loads, terminate
        		clearInterval(ajax_class_timer);
        	}
        	ajax_class_timer_count++;
    	}, 40);
    	
    	
    	
    };
	
};
