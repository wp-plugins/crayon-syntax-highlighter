if (typeof CrayonTagEditorSettings == 'undefined') {
	// WP may have already added it
	CrayonTagEditorSettings = {};
}

// Sets the TINYMCE_USED setting
CrayonTagEditorSettings.setUsed = function(is_used) {
	if (typeof this.ajax_url != 'undefined') {
		if (this.ajax_url && !this.used) {
			is_used = is_used ? '1' : '0';
			this.used = is_used; // Save the setting
			var used_url = this.ajax_url + '?' + this.used_setting + '=' + is_used;
			jQuery.get(used_url);
		}
	}
};

var CrayonTagEditor = new function() {
	
	// VE specific
	var loaded = false;
	var editing = false;
	var insertCallback = null;
	var editCallback = null;
	var editor_name = null;
	var ajax_class_timer = null;
	var ajax_class_timer_count = 0;
	// Shows clear butotn
	var code_refresh = null;
	
	// Current jQuery obj of pre node
	var currCrayon = null;
	// Classes from pre node, excl. settings
	var currClasses = null;
	// Stores the default global settings
//	var defaults = null;
	
	// Generated in WP and contains the settings
	var s = CrayonTagEditorSettings;
	var gs = CrayonSyntaxSettings;
	var admin = CrayonSyntaxAdmin;
	// For use in async functions
	var me = this;
	
	// CSS
	var dialog = code = clear = submit = null;
	var shownOnce = false;
	
	// XXX Loads dialog contents
    this.loadDialog = function() {
    	// Loaded once url is given
    	if (!loaded) {
    		loaded = true;
    	} else {
    		return;
    	}
    	
        // Load the editor content 
        jQuery.get(s.url, function(data) {
        	dialog = jQuery('<div id="'+s.css+'"></div>');
            dialog.appendTo('body').hide();
        	dialog.html(data);
        	
        	dialog.ready(function() {
        		// Some settings have dependencies, need to load js for that
        		admin.init();
        	});
        	
        	me.setOrigValues();
        	
        	// Save default global settings
//        	defaults = [];
//    		jQuery('.'+gs.setting+'[id]').each(function() {
//        		var id = jQuery(this).attr('id');
//        		var value = jQuery(this).attr(s.data_value);
//        		// Remove prefix
////        		id = admin.removePrefixFromID(id);
//        		atts[id] = value;
////        		console_log(id + ' ' + value);
//        	});
        	
        	code = jQuery(s.code_css);
        	clear = jQuery('#crayon-te-clear');
        	code_refresh = function () {
        		var clear_visible = clear.is(":visible");
        		if (code.val().length > 0 && !clear_visible) {
        			clear.show();
        			code.removeClass(gs.selected);
        		} else if (code.val().length <= 0) {
        			clear.hide();
        		}
        	};

        	code.keyup(code_refresh);
        	code.change(code_refresh);
        	clear.click(function() {
        		code.val('');
        		code.removeClass(gs.selected);
        		code.focus();
        	});
        	
        	var setting_change = function() {
    			var setting = jQuery(this);
        		var orig_value = jQuery(this).attr(gs.orig_value);
        		if (typeof orig_value == 'undefined') {
        			orig_value = '';
        		}
        		// Depends on type
        		var value = me.settingValue(setting);
        		var highlight = null;
        		if (setting.is('input[type=checkbox]')) {
    				highlight = setting.next('span'); 
    			}
    			
    			if (orig_value == value) {
    				// No change
    				setting.removeClass(gs.changed);
    				if (highlight) {
    					highlight.removeClass(gs.changed);
    				}
    			} else {
    				// Changed
    				setting.addClass(gs.changed);
    				if (highlight) {
    					highlight.addClass(gs.changed);
    				}
    			}
    			// Save standardized value for later
    			setting.attr(s.data_value, value);
    		};
        	jQuery('.'+gs.setting+'[id]:not(.'+gs.special+')').each(function() {
        		jQuery(this).change(setting_change);
        		jQuery(this).keyup(setting_change);
        	});
        	
        	// Create the Crayon Tag
        	submit = dialog.find('.'+s.submit_css);
        	submit.click(function () {
        		console_log('submit');
    			if (me.addCrayon() != false) {
    				me.hideDialog();
    			}
    		});
        	me.setSubmitText(s.submit_add);
        });
    };
    
    // XXX Displays the dialog.
	this.showDialog = function(insert, edit, editor_str, ed, node) {
		// Need to reset all settings back to original, clear yellow highlighting
		me.resetSettings();
		// If we have selected a Crayon, load in the contents
		// TODO put this in a separate function
		var currNode = null;
		if (typeof node != 'undefined' && node != null) {
			currNode = node;
		} else {
			// Get it from editor selection, not as precise
			currNode = ed != null ? ed.selection.getNode() : null;
		}
		if (currNode != null && currNode.nodeName == 'PRE') {
			currCrayon = jQuery(currNode); 
			if (currCrayon.length != 0) {
				// Read back settings for editing
				currClasses = currCrayon.attr('class');
				var re = new RegExp('\\b([A-Za-z-]+)'+s.attr_sep+'(\\S+)', 'gim');
				var matches = re.execAll(currClasses);
				// Retain all other classes, remove settings
				currClasses = jQuery.trim(currClasses.replace(re, ''));
//				console_log('classes:');
//				console_log(currClasses);
//				console_log('load match:');
//				console_log(matches);
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
				
				// Inverted settings
				if (typeof atts['highlight'] != 'undefined') {
					atts['highlight'] = '0' ? '1' : '0';
				}
				
				// Validate the attributes
				atts = me.validate(atts);
				
				// Load in attributes, add prefix
				for (var att in atts) {
					var setting = jQuery('#' + gs.prefix + att + '.' + gs.setting);
					var value = atts[att];
					me.settingValue(setting, value);
					// Update highlights
					setting.change();
//					console_log('#' + gs.prefix + att + '.' + gs.setting);
					console_log('loaded: ' + att + ':' + atts[att]);
				}
				
				editing = true;
				me.setSubmitText(s.submit_edit);
				code.val(currCrayon.html());
			} else {
				console_log('cannot load currNode of type pre');
			}
		} else {
			// We are creating a new Crayon, not editing
			editing = false;
			me.setSubmitText(s.submit_add);
			currCrayon = null;
			currClasses = null;
		}
		
		// Show the dialog
		var dialog_title = editing ? s.dialog_title_edit : s.dialog_title_add;
    	tb_show(dialog_title, '#TB_inline?inlineId=' + s.css);
    	code.focus();
    	code_refresh();
    	insertCallback = insert;
    	editCallback = edit;
    	editor_name = editor_str;
    	if (ajax_class_timer) {
    		clearInterval(ajax_class_timer);
    		ajax_class_timer_count = 0;
    	}
    	
    	// Position submit button
		jQuery('#TB_title').append(submit);
    	
    	var ajax_window = jQuery('#TB_window');
    	ajax_window.hide();
    	var fallback = function () {
    		ajax_window.show();
    		// Prevent draw artifacts
    		var oldScroll = jQuery(window).scrollTop();
    		jQuery(window).scrollTop(oldScroll+10);
    		jQuery(window).scrollTop(oldScroll-10);
    	};
    	
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
    	
    	s.setUsed(true);
    };
    
    // XXX Add Crayon to editor
    this.addCrayon = function() {
		if (code.val().length == 0) {
			code.addClass(gs.selected);
			code.focus();
			return false;
		} else {
			code.removeClass(gs.selected);
		}
		
		var br_before = br_after = '';
		if (!editing) {
			// Don't add spaces if editting
			if (editor_name == 'html') {
				br_after = br_before = '\n'; 
			} else {
				br_after = '<p>&nbsp;</p>';
			}
		}
		
		var shortcode = br_before + '<pre ';
		
		var atts = {};
		shortcode += 'class="';
		
		// Grab settings as attributes
		jQuery('.'+gs.changed+'[id],.'+gs.changed+'[data-value]').each(function() {
    		var id = jQuery(this).attr('id');
    		var value = jQuery(this).attr(s.data_value);
    		// Remove prefix
    		id = admin.removePrefixFromID(id);
    		atts[id] = value;
//    		console_log(id + ' ' + value);
    	});
		
		// Language
		atts['lang'] = jQuery(s.lang_css).val();
		
		// Ensure mark has no whitespace
		atts['mark'] = jQuery(s.mark_css).val();
		
		// XXX Code highlighting, checked means 0!
		if (jQuery(s.hl_css).is(':checked')) {
			atts['highlight'] = '0';
		}
		
		// XXX Very important when working with editor
    	atts['decode'] = 'true';
    	
    	// Validate the attributes
		atts = me.validate(atts);
    	
		for (var id in atts) {
    		// Remove prefix, if exists
//    		var id = admin.removePrefixFromID(att);
    		var value = atts[id];
    		console_log('add '+id+':'+value);
			shortcode += id + s.attr_sep + value + ' ';
		}
		
		// Add currClasses, if exists
		if (currClasses) {
			shortcode += currClasses;
		}
		// Don't forget to close quote for class
		shortcode += '" ';
		
		var title = jQuery(s.title_css).val();
		if (typeof title != 'undefined') {
			
			shortcode += 'title="' + title + '" ';
		}
		
		var content = jQuery(s.code_css).val();
		content = typeof content != 'undefined' ? content : '';
		shortcode += '>' + content + '</pre>' + br_after;
		
		if (editing) {
			// Edit the current selected node, update refere
			/*currPre =*/ editCallback(shortcode);
		} else {
			// Insert the tag and hide dialog
			insertCallback(shortcode);
		}
		
		return true;
	};
	
	this.hideDialog = function() {
		console_log('hide');
		// Hide dialog
		tb_remove();
		var ajax = jQuery('#TB_ajaxContent');
    	if ( typeof ajax == 'undefined' ) {
    		ajax.removeClass('crayon-te-ajax');
    	}
    	// Title is destroyed, so move the submit out
    	jQuery(s.submit_wrapper_css).append(submit);
	};
	
	// XXX Auxiliary methods
	
	this.setOrigValues = function() {
		jQuery('.'+gs.setting+'[id]').each(function() {
			var setting = jQuery(this);
			setting.attr(gs.orig_value, me.settingValue(setting));
		});
	};
	
	this.resetSettings = function() {
		console_log('reset');
		jQuery('.'+gs.setting).each(function() {
			var setting = jQuery(this);
			me.settingValue(setting, setting.attr(gs.orig_value));
			// Update highlights
			setting.change();
		});
		code.val('');
	};
	
	this.settingValue = function(setting, value) {
		if (typeof value == 'undefined') {
			// getter
			value = '';
			if (setting.is('input[type=checkbox]')) {
				// Boolean is stored as string
				value = setting.is(':checked') ? '1' : '0'; 
			} else {
				value = setting.val();
			}
			return value;
		} else {
			// setter
			if (setting.is('input[type=checkbox]')) {
				if (typeof value == 'string') {
					value = value == '1' ? true : false;
				}
				setting.prop('checked', value);
			} else {
				setting.val(value);
			}
		}
	};
	
	this.validate = function(atts) {
		if (typeof atts['mark'] != 'undefined') {
			atts['mark'] = atts['mark'].replace(/\s/g, '');
		}
		return atts;
	};
	
	this.elemValue = function(obj) {
		var value = null;
		if (obj.is('input[type=checkbox]')) {
			value = obj.is(':checked');
		} else {
			value = obj.val();
		}
		return value;
	};
	
	this.setSubmitText = function(text) {
		submit.val(text);
	}; 
	
};
