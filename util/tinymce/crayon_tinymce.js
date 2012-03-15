var CrayonTinyMCE = function() {
	
	var name = 'crayon_tinymce';
	var btn = name + '_btn';
	var crayon_url = '/crayon_tinymce.php';
	var css = 'crayon-tinymce';
	var crayon_editor = null;
	var ajax_class_timer = null;
	var ajax_class_timer_count = 0;
	var crayon_used, crayon_ajax, crayon_used_setting, crayon_br_before, crayon_br_after, br, br_before, br_after;
	var crayon_code, crayon_clear, crayon_tinymce_warning;
	
    tinymce.PluginManager.requireLangPack(name);
 
    tinymce.create('tinymce.plugins.Crayon', {
        init : function(ed, url) {
        	
        	// Load settings
        	crayon_used = tinyMCE.activeEditor.settings.crayon_used;
        	// If undefined, consider it used
        	crayon_used = (typeof crayon_used == 'undefined') ? true : crayon_used;
        	crayon_ajax = tinyMCE.activeEditor.settings.crayon_ajax;
        	crayon_used_setting = tinyMCE.activeEditor.settings.crayon_used_setting;
        	crayon_br_before = tinyMCE.activeEditor.settings.crayon_br_before;
        	crayon_br_after = tinyMCE.activeEditor.settings.crayon_br_after;
        	br = '<p>&nbsp;</p>';
    		br_before = crayon_br_before ? br : '';
    		br_after = crayon_br_after ? br : '';
        	
            ed.addCommand('showCrayon', function() {
            	if (!crayon_editor) {
            		// Editor not ready, can't do anything
            		return;
            	}
            	
//            	ed.windowManager.open({
//            		// Calls our PHP file to do the dirty work, passes needed vars
//            		//file : url + crayon_url + '?url=' + url,
//					id : name,
//					width : 480,
//					height : 400,
//					title : 'Add Crayon',
//					resizable : true,
//					inline: true,
//					
//				}, {
//					plugin_url : url, // Plugin absolute URL
//					name: name
//				});
            	
            	// TODO calc win size to make
            	tb_show('Add Crayon Code', '#TB_inline?inlineId=' + css);
            	
            	crayon_code.focus();
            	
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
            	
            	// Remove highlight
            	if (crayon_ajax && !crayon_used) {
            		var used_url = crayon_ajax + '?' + crayon_used_setting + '=1';
            		jQuery.get(used_url, function(data) {
            			jQuery('#content_crayon_tinymce').removeClass('mce_crayon_tinymce_highlight');
            		});
            	}
            	
            });
            
            ed.addButton(name, {
            	// TODO add translation
                title: 'Add Crayon Code',
//                image: url + '/' + name + '.png',
                cmd: 'showCrayon'
            });
            
            jQuery(function(){
                // Load the editor content 
                jQuery.get(url + crayon_url + '?url=' + url, function(data) {
                	crayon_editor = jQuery('<div id="'+css+'"></div>');
                    crayon_editor.appendTo('body').hide();
                	crayon_editor.html(data);
                	
                	// Highlight button
                	if (!crayon_used) {
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
            			var shortcode = br_before + '<pre class="crayon-syntax-pre ';
            			var atts = {};
            			
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
            			tinyMCE.activeEditor.execCommand('mceInsertContent', 0, shortcode);
            			
            			tb_remove();
            			var ajax = jQuery('#TB_ajaxContent');
                    	if ( typeof ajax == 'undefined' ) {
                    		ajax.removeClass('crayon-tinymce-ajax');
                    	}
            		});
                });
            });
            
        },
        createControl : function(n, cm){
            return null;
        },
        getInfo : function(){
            return {
                longname: 'Crayon Syntax Highlighter',
                author: 'Aram Kocharyan',
                authorurl: 'http://ak.net84.net/',
                infourl: 'http://bit.ly/crayonsyntax/',
                version: "1.0"
            };
        }
    });
    
    tinymce.PluginManager.add(name, tinymce.plugins.Crayon);
};

CrayonTinyMCE();