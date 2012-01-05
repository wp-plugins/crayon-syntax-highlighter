<!--
// Crayon Syntax Highlighter Admin JavaScript

var DEBUG = false;

// Disables $ for referencing jQuery
jQuery.noConflict();

function crayon_log(string) {
    if (typeof console != 'undefined' && DEBUG) {
        console.log(string);
    }
}

// Not used, # is left unencoded
function crayon_escape(string) {
    if (typeof encodeURIComponent == 'function') {
    	return encodeURIComponent(string);
    } else if (typeof escape != 'function') {
    	return escape(string);
    } else {
    	return string;
    }
}

jQuery(document).ready(function() {
	crayon_log('admin loaded');
	CrayonSyntaxAdmin.init();
});

// Preview
var preview, preview_cbox, preview_url, preview_height, preview_timer, preview_delay_timer, preview_get;
// The DOM object ids that trigger a preview update
var preview_obj_names = [];
// The jQuery objects for these objects
var preview_objs = [];
var preview_last_values = [];
// Alignment
var align_drop, float;
// Toolbar
var overlay, toolbar;
// Error
var msg_cbox, msg;
// Log
var log_button, log_text, log;

var CrayonSyntaxAdmin = new function() {
	
	this.init = function() {
		crayon_log('admin init');
		// Help
		help = jQuery('.crayon-help-close');
		help.click(function() {
			jQuery('.crayon-help').hide();
			jQuery.get(help.attr('url'));
		});
		
		// Preview
		preview = jQuery('#crayon-preview');
		preview_url = preview.attr('url');
		preview_cbox = jQuery('#preview');
		preview_register();
		preview.ready(function() {
			preview_toggle();
		});
		preview_cbox.change(function() { preview_toggle(); });
		
		// Alignment
		align_drop = jQuery('#h_align');
		float = jQuery('#crayon-float');
		align_drop.change(function() { float_toggle(); });
		align_drop.ready(function() { float_toggle(); });
		
	    // Custom Error
	    msg_cbox = jQuery('#error_msg_show');
	    msg = jQuery('#error_msg');
	    toggle_error();
	    msg_cbox.change(function() { toggle_error(); });
	
	    // Toolbar
	    overlay = jQuery('#toolbar_overlay');
	    toolbar = jQuery('#toolbar');
	    toggle_toolbar();
	    toolbar.change(function() { toggle_toolbar(); });
	    
	    // Copy
	    plain = jQuery('#plain');
	    copy = jQuery('#crayon-copy-check');
	    plain.change(function() {
	    	if (plain.is(':checked')) {
	    		copy.show();
	    	} else {
	    		copy.hide();
	    	}
		});
	    
	    // Log
	    var show_log = 'Show Log';
	    var hide_log = 'Hide Log';
	    log_wrapper = jQuery('#crayon-log-wrapper');
	    log_button = jQuery('#crayon-log-toggle');
	    log_text = jQuery('#crayon-log-text');
	    clog = jQuery('#crayon-log');
	    log_button.val(show_log);
	    log_button.click(function() {
	    	clog.width(log_wrapper.width());
	    	clog.toggle();
	        // Scrolls content
	        clog.scrollTop(log_text.height());
	        var text = ( log_button.val() == show_log ? hide_log : show_log );
	        log_button.val(text);
	    });
	}
	
	/* Whenever a control changes preview */
	var preview_update = function() {
		crayon_log('preview_update');
		preview_get = '?';
		var val = 0;
		var obj;
		for (i = 0; i < preview_obj_names.length; i++) {
			obj = preview_objs[i];
			if (obj.attr('type') == 'checkbox') {
				val = obj.is(':checked');
			} else {
				val = obj.val();
			}
			preview_get += preview_obj_names[i] + '=' + crayon_escape(val) + "&";
		}
		
		// XXX Scroll to top of themes
		// Disabled for now, too annoying
		//var top = jQuery('a[name="crayon-theme"]');
		//jQuery(window).scrollTop(top.position().top);
		
		// Delay resize
		preview.css('height', preview.height());
		preview.css('overflow', 'hidden');
		preview_timer = setInterval(function() {
			preview.css('height', '');
			preview.css('overflow', 'visible');
			clearInterval(preview_timer);
		}, 1000);
	
		// Load Data
		jQuery.get(preview_url + preview_get, function(data) {
			//crayon_log(data);
			preview.html(data);
			// Important! Calls the crayon.js init
			CrayonSyntax.init();
		});
	}
	
	var bool_to_int = function(bool) {
		if (bool == true) {
			return 1;
		} else {
			return 0;
		}
	}
	
	var preview_toggle = function() {
		crayon_log('preview_toggle');
	    if ( preview_cbox.is(':checked') ) {
	    	preview.show();
	    	preview_update();
	    } else {
	    	preview.hide();
	    }
	}
	
	var float_toggle = function() {
	    if ( align_drop.val() != 0 ) {
	    	float.show();
	    } else {
	    	float.hide();
	    }
	}
	
	// List of callbacks
	var preview_callback;
	var preview_txt_change;
	var preview_txt_callback; // Only updates if text value changed
	var preview_txt_callback_delayed;
	//var height_set;
	
	// Register all event handlers for preview objects
	var preview_register = function() {
		crayon_log('preview_register');
		var obj;
		preview_get = '?';
	
		// Instant callback
		preview_callback = function() {
			preview_update();
		}
		
		// Checks if the text input is changed, if so, runs the callback with given event
		preview_txt_change = function(callback, event) {
			//crayon_log('checking if changed');
			var obj = event.target;
			var last = preview_last_values[obj.id];
			//crayon_log('last' + preview_last_values[obj.id]);
			
			if (obj.value != last) {
				//crayon_log('changed');
				// Update last value to current
				preview_last_values[obj.id] = obj.value;
				// Run callback with event
				callback(event);
			}
		}
		
		// Only updates when text is changed
		preview_txt_callback = function(event) {
			//crayon_log('txt callback');
			preview_txt_change(preview_update, event);
		}
		
		// Only updates when text is changed, but  callback
		preview_txt_callback_delayed = function(event) {
			//crayon_log('txt delayed');
			
			preview_txt_change(function() {
				clearInterval(preview_delay_timer);
				preview_delay_timer = setInterval(function() {
					//crayon_log('delayed update');				
					preview_update();
					clearInterval(preview_delay_timer);
				}, 500);
			}, event);
		}
		
		// Retreive preview objects
		jQuery('[crayon-preview="1"]').each(function(i) {
			var obj = jQuery(this);
			preview_obj_names[i] = obj.attr('id');
			preview_objs[i] = obj;
			// To capture key up events when typing
			if (obj.attr('type') == 'text') {
				preview_last_values[obj.attr('id')] = obj.val();
				obj.bind('keyup', preview_txt_callback_delayed);
				obj.change(preview_txt_callback);
			} else {
				// For all other objects
				obj.change(preview_callback);
			}
		});
	}
	
	var toggle_error = function() {
	    if ( msg_cbox.is(':checked') ) {
	        msg.show();
	    } else {
	        msg.hide();
	    }
	}
	
	var toggle_toolbar = function() {
	    if ( toolbar.val() == 0 ) {
	        overlay.show();
	    } else {
	        overlay.hide();
	    }
	}
	
	var show_langs = function(url) {
		jQuery('#show-lang').hide();
		jQuery.get(url, function(data) {
			jQuery('#lang-info').show();
			jQuery('#lang-info').html(data);
		});
	}
	
}

//-->