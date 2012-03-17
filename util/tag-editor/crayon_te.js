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
			jQuery.get(used_url);
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
	// Generated in WP and contains the settings
	var settings = CrayonTagEditorSettings;
	// For use in async functions
	var te = this;
	
//	var br_before, br_after;
	
	// CSS
	var dialog, code, clear;
	// True if editing an existing Crayon
	var editing = false;
	
	// XXX Loads dialog contents
    this.loadDialog = function() {    	
    	// Loaded once url is given
    	if (!loaded) {
    		loaded = true;
    	} else {
    		return;
    	}
    	
        // Load the editor content 
        jQuery.get(settings.url, function(data) {
        	dialog = jQuery('<div id="'+settings.css+'"></div>');
            dialog.appendTo('body').hide();
        	dialog.html(data);
        	
        	code = jQuery(settings.code_css);
        	clear = jQuery('#crayon-te-clear');
        	var code_refresh = function () {
        		var clear_visible = clear.is(":visible");
        		if (code.val().length > 0 && !clear_visible) {
        			clear.show();
        			code.removeClass('crayon-setting-selected');
        		} else if (code.val().length <= 0) {
        			clear.hide();
        		}
        	}

        	code.keyup(code_refresh);
        	code.change(code_refresh);
        	clear.click(function() {
        		code.val('');
        		code.removeClass('crayon-setting-selected');
        		code.focus();
        	});
        	
        	var setting_change = function() {
    			var me = jQuery(this);
    			var id = jQuery(this).attr('id');
        		var orig_value = jQuery(this).attr('data-orig-value');
        		if (typeof orig_value == 'undefined') {
        			orig_value = '';
        		}
        		// Depends on type
        		var value = '';
        		var highlight = null;
    			if (me.is('input[type=checkbox]')) {
    				value = me.is(':checked') ? '1' : '0';
    				highlight = me.next('span'); 
    			} else {
    				value = me.val();
    			}
    			if (typeof value == 'undefined') {
    				value = '';
        		}
    			
    			if (orig_value == value) {
    				// No change
    				me.removeClass('crayon-setting-changed');
    				if (highlight) {
    					highlight.removeClass('crayon-setting-changed');
    				}
    			} else {
    				// Changed
    				me.addClass('crayon-setting-changed');
    				if (highlight) {
    					highlight.addClass('crayon-setting-changed');
    				}
    			}
    			// Save the value for later
    			me.attr('data-value', value);
    		};
        	jQuery('.crayon-setting[id]').each(function() {
        		jQuery(this).change(setting_change);
        		jQuery(this).keyup(setting_change);
        	});
        	
        	// Create the Crayon Tag
        	dialog.find('#crayon-te-submit').click(te.addCrayon());
        });
    };
    
    // XXX Displays the dialog
	this.showDialog = function(callback, editor_str, ed) {
		// If we have selected a Crayon, load in the contents
		var currNode = ed.selection.getNode();
		if (currNode.nodeName == 'PRE') {
			currCrayon = jQuery(currNode);
			editing = currCrayon.hasClass(settings.pre_css); 
			if (editing) {
				var class_ = currCrayon.attr('class');
				var attr_regex = new RegExp('\\b([A-Za-z-]+)'+settings.attr_sep+'(\\S+)', 'gim');
				var matches = attr_regex.execAll(class_);
				var atts = {};
				for (var i in matches) {
					var id = matches[i][1];
					var value = matches[i][2];
					atts[id] = value;
				}
				// Only read title, don't let other atts in, no need
				var title = currCrayon.attr('title');
				if (title) {
					atts['title'] = title;
				}
				
				// Load in attributes
				for (var att in atts) {
					jQuery('#' + att + '.' + 'crayon-setting').val(atts[att]);
					console.log(att + ' ' + atts[att]);
				}
				
				code.val(currCrayon.html());
			}
		}
		
		// Show the dialog
		
    	tb_show('Add Crayon Code', '#TB_inline?inlineId=' + settings.css);
    	code.focus();
    	insertCallback = callback;
    	editor_name = editor_str;
    	if (ajax_class_timer) {
    		clearInterval(ajax_class_timer);
    		ajax_class_timer_count = 0;
    	}
    	
    	var ajax_window = jQuery('#TB_window');
    	ajax_window.hide();
    	var fallback = function () {
    		ajax_window.show();
    		// Prevent draw artifacts
    		var oldScroll = jQuery(window).scrollTop();
    		jQuery(window).scrollTop(oldScroll+10);
    		jQuery(window).scrollTop(oldScroll-10);
    	}
    	
    	ajax_class_timer = setInterval(function () {
        	if ( typeof ajax_window != 'undefined' && !ajax_window.hasClass('crayon-te-ajax') ) {
        		ajax_window.addClass('crayon-te-ajax');
        		clearInterval(ajax_class_timer);
        		fallback();
        	}
        	if (ajax_class_timer_count >= 100) {
        		// In case it never loads, terminate
        		clearInterval(ajax_class_timer);
        		fallback();
        	}
        	ajax_class_timer_count++;
    	}, 40);
    	
    	settings.setUsed(true);
    };
    
    // XXX Add Crayon to editor
    this.addCrayon =  function() {
		if (code.val().length == 0) {
			code.addClass('crayon-setting-selected');
			code.focus();
			return false;
		} else {
			code.removeClass('crayon-setting-selected');
		}
		
		var br_before = br_after = '';
		if (editor_name == 'html') {
			br_after = br_before = '\n'; 
		} else {
			br_after = '<p>&nbsp;</p>';
		}
		
		var shortcode = br_before + '<pre ';
		
		var atts = {};
		shortcode += 'class="'+settings.pre_css+' '; 
		
		// Grab settings as attributes
		jQuery('.crayon-setting-changed[id],.crayon-setting-changed[data-value]').each(function() {
    		var id = jQuery(this).attr('id');
    		var value = jQuery(this).attr('data-value');
    		atts[id] = value;
    		console.log(id + ' ' + value);
    	});
		
		// Always add language
		jQuery(settings.lang_css).each(function() {
			var value = jQuery(this).val() || '';
			atts[settings.lang_css] = value;
		});
		
		// Ensure mark has no whitespace
		jQuery(settings.mark_css).each(function() {
			var value = jQuery(this).val();
			if (value.length != 0) {
				atts[settings.mark_css] = value.replace(/\s/g, '');
			}
		});
		
    	atts['decode'] = 'true';
		for (var att in atts) {
    		// Remove prefix, if exists
    		var id = att.replace(/^#?crayon-/, '');
    		var value = atts[att];
    		console.log('att: id: '+id+' value: '+value);
			shortcode += id + settings.attr_sep + value + ' ';
		}
		// Don't forget to close quote for class
		shortcode += '" ';
		
		var title = jQuery(settings.title_css).val();
		if (typeof title != 'undefined') {
			
			shortcode += 'title="' + title + '" ';
		}
		
		var content = jQuery(settings.code_css).val();
		content = typeof content != 'undefined' ? content : '';
		shortcode += '>' + content + '</pre>' + br_after;
		
		// Insert the tag and hide dialog
		insertCallback(shortcode);
		
		tb_remove();
		var ajax = jQuery('#TB_ajaxContent');
    	if ( typeof ajax == 'undefined' ) {
    		ajax.removeClass('crayon-te-ajax');
    	}
	}
	
};
