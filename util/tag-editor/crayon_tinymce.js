var CrayonTinyMCE = new function() {
	
	// TinyMCE specific
	var name = 'crayon_tinymce';
	var settings = CrayonTagEditorSettings;
	var isHighlighted = false;
	var currPre = null;
//	var wasHighlighted = false;
	
	this.setHighlight = function(highlight) {
		if (highlight) {
			jQuery('#content_crayon_tinymce').addClass('mce_crayon_tinymce_highlight');
		} else {
			jQuery('#content_crayon_tinymce').removeClass('mce_crayon_tinymce_highlight');
		}
		isHighlighted = highlight;
	}
	
	this.loadTinyMCE = function() {
	    tinymce.PluginManager.requireLangPack(name);
	    
	    tinymce.create('tinymce.plugins.Crayon', {
	        init : function(ed, url) {
	    		jQuery(function() {
	    			CrayonTagEditor.loadDialog();
	        	});
	    		
	    		ed.onInit.add(function(ed) {
	    			ed.dom.loadCSS(url + '/crayon_te.css');
				});
				
	    		// Prevent <p> on enter, turn into \n
				ed.onKeyDown.add(function( ed, e ) {
					var selection = ed.selection;
					if ( e.keyCode == 13 && selection.getNode().nodeName == 'PRE' ) {
						selection.setContent('\n', {format : 'raw'});
						return tinymce.dom.Event.cancel(e);
					}
				});
	    		
	    		ed.onInit.add(function(ed) {
	    			console.log(tinyMCE.activeEditor.settings.wpautop);
    				CrayonTinyMCE.setHighlight(!settings.used);
	    	    });
	    		
	            ed.addCommand('showCrayon', function() {
	            	CrayonTagEditor.showDialog(function(shortcode) {
	            		tinyMCE.activeEditor.execCommand('mceInsertContent', 0, shortcode);
	            	}, 'tinymce', ed);
	            	CrayonTinyMCE.setHighlight(false);
	            });
	            
	            ed.onNodeChange.add(function(ed, cm, n, co) {
	            	if (n != currPre) {
	            		// We don't care if we select the same object
	            		if (currPre) {
			            	// If we have a previous pre, remove it
		        			jQuery(currPre).removeClass(settings.pre_css_selected);
		        			currPre = null;
		        		}
		            	if (n.nodeName == 'PRE') {
		            		// Add new pre
		            		currPre = n;
		            		jQuery(n).addClass(settings.pre_css_selected);
		            		CrayonTinyMCE.setHighlight(true);
		            	} else {
		            		// No pre selected
		            		CrayonTinyMCE.setHighlight(!settings.used);
		            	}
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
