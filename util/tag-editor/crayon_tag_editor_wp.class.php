<?php

require_once (CRAYON_ROOT_PATH . 'crayon_settings_wp.class.php');
//require_once (CRAYON_UTIL_PHP);

class CrayonTagEditorWP {

	public static $settings = null;
	
	public static function init() {
		self::init_settings();
		// Hooks
		if (CRAYON_TAG_EDITOR) {
			self::addbuttons();
			add_filter('tiny_mce_before_init', 'CrayonTagEditorWP::init_tinymce');
			add_action("admin_print_scripts-post.php", 'CrayonTagEditorWP::admin_scripts');
			// Must come after
			add_action("admin_print_scripts-post.php", 'CrayonSettingsWP::init_js_settings');
		}
	}
	
	public static function init_settings() {
		if (!self::$settings) {
			// Add settings
			CrayonSettingsWP::load_settings(TRUE);
			self::$settings = array(
					'url' => plugins_url(CRAYON_TE_PHP, __FILE__),
					'home_url' => home_url(),
					'css' => 'crayon-te',
					'used' => CrayonGlobalSettings::val(CrayonSettings::TINYMCE_USED),
					'used_setting' => CrayonSettings::TINYMCE_USED,
					'ajax_url' => plugins_url(CRAYON_AJAX_PHP, dirname(dirname(__FILE__))),
// 					'pre_css' => 'crayon-syntax-pre',
					'css_selected' => 'crayon-selected',
					'code_css' => '#crayon-te-code',
					'lang_css' => '#crayon-lang',
					'title_css' => '#crayon-title',
					'mark_css' => '#crayon-mark',
					'switch_html' => '#content-html',
					'switch_tmce' => '#content-tmce',
					'submit_css' => 'crayon-te-submit',
					'attr_sep' => ':',
					'dialog_title' => 'Add Crayon Code',
					'submit_add' => 'Add Crayon',
					'submit_edit' => 'Save Crayon',
			);
		}
	}
	
	public static function init_tinymce($init) {
		$init['extended_valid_elements'] .= ',pre[*],code[*],iframe[*]';
		
//		$init['convert_newlines_to_brs'] = TRUE;
//		$init['remove_linebreaks'] = false;
//		$init['wpautop'] = false;

//		$init['forced_root_block'] = false;
//		$init['force_p_newlines'] = false;
//		$init['remove_linebreaks'] = false;
//		$init['remove_trailing_nbsp'] = false;
//		$init['verify_html'] = false;
//		$init['force_br_newlines'] = true;
		
		

		
//		protect
		
		
//		$init['crayon_used'] = CrayonGlobalSettings::val(CrayonSettings::TINYMCE_USED);
//		$init['crayon_ajax'] = plugins_url(CRAYON_AJAX_PHP, dirname(__FILE__));
//		$init['crayon_used_setting'] = CrayonSettings::TINYMCE_USED;
//		$line_break = CrayonGlobalSettings::val(CrayonSettings::TINYMCE_LINE_BREAK);
//		$init['crayon_br_after'] = $line_break == 0 || $line_break == 1;
//		$init['crayon_br_before'] = $line_break == 0 || $line_break == 2;
		
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
			add_filter('mce_external_plugins', 'CrayonTagEditorWP::add_plugin');
			add_filter('mce_buttons', 'CrayonTagEditorWP::register_buttons');
		}
	}
	
	public static function admin_scripts() {
		global $CRAYON_VERSION;
		wp_enqueue_script('crayon_util_js', plugins_url(CRAYON_JS_UTIL, dirname(dirname(__FILE__))), NULL, $CRAYON_VERSION);
		wp_enqueue_script('crayon_admin_js', plugins_url(CRAYON_JS_ADMIN, dirname(dirname(__FILE__))), array('jquery', 'crayon_util_js'), $CRAYON_VERSION, TRUE);
		wp_enqueue_script('crayon_te_js', plugins_url(CRAYON_TE_JS, __FILE__), array('crayon_admin_js'), $CRAYON_VERSION);
		wp_enqueue_script('crayon_qt_js', plugins_url(CRAYON_QUICKTAGS_JS, __FILE__), array('quicktags'. 'crayon_te_js'), $CRAYON_VERSION, TRUE);
		wp_localize_script('crayon_te_js', 'CrayonTagEditorSettings', self::$settings);
	}
	 
	public static function register_buttons($buttons) {
		array_push($buttons, 'separator', 'crayon_tinymce');
		return $buttons;
	}
	 
	// Load the TinyMCE plugin : editor_plugin.js (wp2.5)
	public static function add_plugin($plugin_array) {
		$plugin_array['crayon_tinymce'] = plugins_url(CRAYON_TINYMCE_JS, __FILE__);
		return $plugin_array;
	}
	
}

if (defined('ABSPATH') && is_admin()) {
	add_action('init', 'CrayonTagEditorWP::init');
}

?>