var CrayonTinyMCE = new function() {
	
	// TinyMCE specific
	var name = 'crayon_tinymce';
	// A copy of the settings
	var settings = CrayonTagEditorSettings;
//	var btn = name + '_btn';
//	var crayon_used, crayon_ajax, crayon_used_setting, crayon_br_before, crayon_br_after, crayon_br;
//	var crayon_code, crayon_clear, crayon_tinymce_warning;
	
	this.loadTinyMCE = function() {
	    tinymce.PluginManager.requireLangPack(name);
	 
	    tinymce.create('tinymce.plugins.Crayon', {
	        init : function(ed, url) {

	    		jQuery(function() {
	    			CrayonTagEditor.load();
	        	});
	    		
	    		ed.onInit.add(function(ed) {
	    			if (!settings.used) {
	    				jQuery('#content_crayon_tinymce').addClass('mce_crayon_tinymce_highlight');
	    			}
	    	    });
	    		
	            ed.addCommand('showCrayon', function() {
	            	CrayonTagEditor.dialog(function(shortcode) {
	            		tinyMCE.activeEditor.execCommand('mceInsertContent', 0, shortcode);
	            	}, 'tinymce');
	            	jQuery('#content_crayon_tinymce').removeClass('mce_crayon_tinymce_highlight');
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
