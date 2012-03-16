
console.log('load tiny');

// Must load after CrayonVisualEditor

var CrayonTinyMCE = new function() {
	
	// TinyMCE specific
	var name = 'crayon_tinymce';
	var btn = name + '_btn';
	var crayon_used, crayon_ajax, crayon_used_setting, crayon_br_before, crayon_br_after, crayon_br;
	var crayon_code, crayon_clear, crayon_tinymce_warning;
	
	this.loadTinyMCE = function() {
	    tinymce.PluginManager.requireLangPack(name);
	 
	    tinymce.create('tinymce.plugins.Crayon', {
	        init : function(ed, url) {
	        	
	        	// Load settings
	        	crayon_used = tinyMCE.activeEditor.settings.crayon_used;
	        	crayon_ajax = tinyMCE.activeEditor.settings.crayon_ajax;
	        	crayon_used_setting = tinyMCE.activeEditor.settings.crayon_used_setting;
	        	crayon_br_before = tinyMCE.activeEditor.settings.crayon_br_before;
	        	crayon_br_after = tinyMCE.activeEditor.settings.crayon_br_after;
	        	crayon_br = '<p>&nbsp;</p>';
	        	
	        	var args = {
	        			url : url,
	        			used : crayon_used,
	        			br_before : crayon_br_before,
	        			br_after : crayon_br_after,
	        			br : crayon_br
	        			};
	    		jQuery(function() {CrayonVisualEditor.load(args)});
	    		
	            ed.addCommand('showCrayon', function() {
	            	// Load back the args
	            	CrayonVisualEditor.settings(args);
	            	
	            	CrayonVisualEditor.dialog(function(shortcode) {
	            		tinyMCE.activeEditor.execCommand('mceInsertContent', 0, shortcode);
	            	});
	            	// Remove highlight
	            	if (crayon_ajax && !crayon_used) {
	            		// TODO
	            		var used_url = crayon_ajax + '?' + crayon_used_setting + '=1';
	            		jQuery.get(used_url, function(data) {
	            			jQuery('#content_crayon_tinymce').removeClass('mce_crayon_tinymce_highlight');
	            		});
	            	}
	            });
	            
	            ed.addButton(name, {
	            	// TODO add translation
	                title: 'Add Crayon Code',
	                cmd: 'showCrayon'
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
	
	// Load TinyMCE
	this.loadTinyMCE();
	
};
