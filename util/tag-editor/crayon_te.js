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
	
	// Current jQuery obj of pre node
	var currCrayon = null;
	// Classes from pre node, excl. settings
	var currClasses = null;
	
	// Generated in WP and contains the settings
	var s = CrayonTagEditorSettings;
	var gs = CrayonSyntaxSettings;
	var admin = CrayonSyntaxAdmin;
	// For use in async functions
	var me = this;
	
	// CSS
	var dialog = code = clear = submits = null;
	
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
        	
        	code = jQuery(s.code_css);
        	clear = jQuery('#crayon-te-clear');
        	var code_refresh = function () {
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
    			var me = jQuery(this);
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
    				me.removeClass(gs.changed);
    				if (highlight) {
    					highlight.removeClass(gs.changed);
    				}
    			} else {
    				// Changed
    				me.addClass(gs.changed);
    				if (highlight) {
    					highlight.addClass(gs.changed);
    				}
    			}
    			// Save the value for later
    			me.attr('data-value', value);
    		};
        	jQuery('.'+gs.setting+'[id]:not(.'+gs.special+')').each(function() {
        		jQuery(this).change(setting_change);
        		jQuery(this).keyup(setting_change);
        	});
        	
        	// Create the Crayon Tag
        	submits = dialog.find('.'+s.submit_css);
        	submits.each(function() {
        		jQuery(this).click(function () {
        			console.log(me);
            		me.addCrayon();
            	});
        	});
        	me.setSubmitTest(s.submit_add);
        });
    };
    
    // XXX Displays the dialog.
	this.showDialog = function(insert, edit, editor_str, ed) {
		// If we have selected a Crayon, load in the contents
		var currNode = ed.selection.getNode();
		if (currNode.nodeName == 'PRE') {
			currCrayon = jQuery(currNode);
//			editing = currCrayon.hasClass(s.pre_css); 
			if (currCrayon.length != 0) {
				// Read back settings for editing
				currClasses = currCrayon.attr('class');
				var re = new RegExp('\\b([A-Za-z-]+)'+s.attr_sep+'(\\S+)', 'gim');
				var matches = re.execAll(currClasses);
				// Retain all other classes, remove settings
				currClasses = jQuery.trim(currClasses.replace(re, ''));
				console_log('classes:');
				console_log(currClasses);
				console_log('load match:');
				console_log(matches);
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
				
				// Load in attributes, add prefix
				for (var att in atts) {
					jQuery('#' + gs.prefix + att + '.' + gs.setting).val(atts[att]);
					console_log('#' + gs.prefix + att + '.' + gs.setting);
					console_log('loaded: ' + att + ':' + atts[att]);
				}
				
				editing = true;
				me.setSubmitTest(s.submit_edit);
				code.val(currCrayon.html());
			} else {
				console_log('cannot load currNode of type pre');
			}
		} else {
			// We are creating a new Crayon, not editing
			editing = false;
			me.setSubmitTest(s.submit_add);
			currCrayon = null;
			currClasses = null;
			// TODO clear settings?
		}
		
		// Show the dialog
		
    	tb_show(s.dialog_title, '#TB_inline?inlineId=' + s.css);
    	code.focus();
    	insertCallback = insert;
    	editCallback = edit;
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
    this.addCrayon =  function() {
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
    		var value = jQuery(this).attr('data-value');
    		// Remove prefix
//    		id = admin.removePrefixFromID(id);
    		atts[id] = value;
//    		console_log(id + ' ' + value);
    	});
		
		// Always add language
		jQuery(s.lang_css).each(function() {
			var value = jQuery(this).val() || '';
			atts[s.lang_css] = value;
		});
		
		// Ensure mark has no whitespace
		jQuery(s.mark_css).each(function() {
			var value = jQuery(this).val();
			if (value.length != 0) {
				atts[s.mark_css] = value.replace(/\s/g, '');
			}
		});
		
    	atts['decode'] = 'true';
		for (var att in atts) {
    		// Remove prefix, if exists
    		var id = admin.removePrefixFromID(att);
    		var value = atts[att];
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
			// Edit the current selected node
			editCallback(shortcode);
		} else {
			// Insert the tag and hide dialog
			insertCallback(shortcode);
		}
		
		tb_remove();
		var ajax = jQuery('#TB_ajaxContent');
    	if ( typeof ajax == 'undefined' ) {
    		ajax.removeClass('crayon-te-ajax');
    	}
	};
	
	this.setSubmitTest = function(text) {
		if (submits) {
			submits.each(function() {
        		jQuery(this).val(text);
        	});
		}
	}; 
	
};
