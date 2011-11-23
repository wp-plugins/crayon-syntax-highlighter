<?php
require_once ('global.php');
require_once (CRAYON_RESOURCE_PHP);

/* Manages themes once they are loaded. */
class CrayonThemes extends CrayonUsedResourceCollection {
	// Properties and Constants ===============================================

	const DEFAULT_THEME = 'classic';
	const DEFAULT_THEME_NAME = 'Classic';

	private $printed_themes = array();
	
	// Methods ================================================================

	function __construct() {
		$this->directory ( CRAYON_THEME_PATH );
		$this->set_default ( self::DEFAULT_THEME, self::DEFAULT_THEME_NAME );
	}

	// XXX Override

	public function path($id) {
		return CRAYON_THEME_PATH . $id . "/$id.css";
	}
	
	// Prints out CSS for all the used themes
	public function get_used_theme_css_str() {
		$css_str = '';
		$css = self::get_used_theme_css();
		foreach ($css as $theme=>$url) {
			$css_str .= '<link rel="stylesheet" type="text/css" href="' . $css[$url] . '" />' . CRAYON_NL;
		}
		return $css_str;
	}
	
	public function get_theme_url($theme) {
		return CrayonGlobalSettings::plugin_path() . CrayonUtil::pathf(CRAYON_THEME_DIR) . $theme->id() . '/' . $theme->id() . '.css';
	}
	
	public function get_theme_as_css($theme) {
		$css_str = '<link rel="stylesheet" type="text/css" href="' . self::get_theme_url($theme) . '" />' . CRAYON_NL;
		return $css_str;
	}
	
	public function get_used_theme_css() {
		CrayonLog::log('get_used_theme_css');
		$used = $this->get_used();
		CrayonLog::log($used, 'used');
		$css = array();
		foreach ($used as $theme) {
			$url = self::get_theme_url($theme);
			CrayonLog::log($url, 'url');
			$css[$theme->id()] = $url;
		}
		CrayonLog::log($css, 'css');
		return $css;
	}
	
}
?>