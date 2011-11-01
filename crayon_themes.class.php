<?php
require_once ('global.php');
require_once (CRAYON_RESOURCE_PHP);

/* Manages themes once they are loaded. */
class CrayonThemes extends CrayonUsedResourceCollection {
	// Properties and Constants ===============================================

	const DEFAULT_THEME = 'classic';
	const DEFAULT_THEME_NAME = 'Classic';

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
	public function get_used_theme_css() {
		global $CRAYON_VERSION;
		$used = $this->get_used();
		$css = '';
		foreach ($used as $theme) {
			$url = CrayonGlobalSettings::plugin_path() . CrayonUtil::pathf(CRAYON_THEME_DIR) . $theme->id() . '/' . $theme->id() . '.css?ver' . $CRAYON_VERSION;
			$css .= '<link rel="stylesheet" type="text/css" href="' . $url . '" />' . CRAYON_NL;
		}
		return $css;
	}
}
?>