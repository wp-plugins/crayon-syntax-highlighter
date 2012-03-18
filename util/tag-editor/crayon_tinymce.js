var CrayonTinyMCE = new function() {
	
	// TinyMCE specific
	var name = 'crayon_tinymce';
	var settings = CrayonTagEditorSettings;
	var isHighlighted = false;
	var currPre = null;
	// Switch events
	var switch_html_click = switch_tmce_click = null;
	
	var me = this;
//	var wasHighlighted = false;
	
	this.setHighlight = function(highlight) {
		if (highlight) {
			jQuery('#content_crayon_tinymce').addClass('mce_crayon_tinymce_highlight');
		} else {
			jQuery('#content_crayon_tinymce').removeClass('mce_crayon_tinymce_highlight');
		}
		isHighlighted = highlight;
	};
	
	this.selectPreCSS = function(selected) {
		if (currPre) {
			if (selected) {
				jQuery(currPre).addClass(settings.css_selected);
			} else {
				jQuery(currPre).removeClass(settings.css_selected);
			}
    	}
	};
	
	this.isPreSelectedCSS = function() {
		if (currPre) {
			return jQuery(currPre).hasClass(settings.css_selected);
		}
		return false;
	};
	
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
    				me.setHighlight(!settings.used);
	    	    });
	    		
	            ed.addCommand('showCrayon', function() {
	            	CrayonTagEditor.showDialog(function(shortcode) {
	            		ed.execCommand('mceInsertContent', 0, shortcode);
	            	},
	            	function(shortcode) {
	            		// This will change the currPre object
	            		var newPre = jQuery(shortcode);
	            		jQuery(currPre).replaceWith(newPre);
	            		currPre = newPre;
	            	}, 'tinymce', ed);
	            	
	            	if (!currPre) {
	            		// If no pre is selected, then button highlight depends on if it's used 
	            		me.setHighlight(!settings.used);
	            	}
	            });
	            
	            // Remove onclick and call ourselves
	            var switch_html = jQuery(settings.switch_html);
//	            switch_html_click = switch_html.prop('onclick');
	            switch_html.prop('onclick', null);
	            switch_html.click(function() {
	            	// Remove selected pre class when switching to HTML editor
	            	me.selectPreCSS(false);
	            	switchEditors.go('content','html');
//	            	switch_html_click();
	            });
	            
	            // Remove onclick and call ourselves
	            var switch_tmce = jQuery(settings.switch_tmce);
//	            switch_tmce_click = switch_tmce.prop('onclick');
	            switch_tmce.prop('onclick', null);
	            switch_tmce.click(function() {
	            	// Add selected pre class when switching to back to TinyMCE
//	            	if (!me.isPreSelectedCSS()) {
//	            		me.selectPreCSS(true);
//	            	}
	            	switchEditors.go('content','tmce');
//	            	switch_tmce_click();
	            });
	            
	            // Highlight selected 
	            ed.onNodeChange.add(function(ed, cm, n, co) {
	            	if (n != currPre) {
	            		// We only care if we select another same object
	            		if (currPre) {
			            	// If we have a previous pre, remove it
	            			me.selectPreCSS(false);
		        			currPre = null;
		        		}
		            	if (n.nodeName == 'PRE') {
		            		// Add new pre
		            		currPre = n;
		            		me.selectPreCSS(true);
		            		me.setHighlight(true);
		            	} else {
		            		// No pre selected
		            		me.setHighlight(!settings.used);
		            	}
	            	}
				});
	            
	            ed.addButton(name, {
	            	// TODO add translation
	                title: settings.dialog_title,
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
