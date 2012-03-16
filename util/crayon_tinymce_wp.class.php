<?php

require_once (CRAYON_ROOT_PATH . 'crayon_settings_wp.class.php');

class CrayonTinyMCEWP {

	public static function init() {
		self::addbuttons();
		add_filter('tiny_mce_before_init', 'CrayonTinyMCEWP::init_tinymce');
		add_action("admin_print_scripts-post.php", 'CrayonTinyMCEWP::admin_scripts');
	}
	
	public static function init_tinymce($init) {
		$init['extended_valid_elements'] .= ',pre[*],code[*],iframe[*]';
		CrayonSettingsWP::load_settings(TRUE);
		$init['crayon_used'] = CrayonGlobalSettings::val(CrayonSettings::TINYMCE_USED);
		$init['crayon_ajax'] = plugins_url(CRAYON_AJAX_PHP, dirname(__FILE__));
		$init['crayon_used_setting'] = CrayonSettings::TINYMCE_USED;
		$line_break = CrayonGlobalSettings::val(CrayonSettings::TINYMCE_LINE_BREAK);
		$init['crayon_br_after'] = $line_break == 0 || $line_break == 1;
		$init['crayon_br_before'] = $line_break == 0 || $line_break == 2;
		
//		$init['crayon_add_overridden'] = CrayonGlobalSettings::val(CrayonSettings::TINYMCE_ADD_OVERRIDDEN);
		return $init;
	}
	
	public static function addbuttons() {
		// Don't bother doing this stuff if the current user lacks permissions
		if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') ) {
	   		return;
		}
		
		// Add only in Rich Editor mode
		if ( get_user_option('rich_editing') == 'true') {
			add_filter('mce_external_plugins', 'CrayonTinyMCEWP::add_plugin');
			add_filter('mce_buttons', 'CrayonTinyMCEWP::register_buttons');
		}
	}
	
	public static function admin_scripts() {
		global $CRAYON_VERSION;
		wp_enqueue_script('crayon_ve_js', plugins_url(CRAYON_VE_JS, dirname(__FILE__)), array('jquery'), $CRAYON_VERSION);
		wp_enqueue_script('crayon_quicktags_js', plugins_url(CRAYON_QUICKTAGS_JS, dirname(__FILE__)), array('quicktags'), $CRAYON_VERSION, TRUE);
	}
	 
	public static function register_buttons($buttons) {
		array_push($buttons, 'separator', 'crayon_tinymce');
		return $buttons;
	}
	 
	// Load the TinyMCE plugin : editor_plugin.js (wp2.5)
	public static function add_plugin($plugin_array) {
		$plugin_array['crayon_tinymce'] = plugins_url(CRAYON_TINYMCE_JS, dirname(__FILE__));
		return $plugin_array;
	}

}

?>