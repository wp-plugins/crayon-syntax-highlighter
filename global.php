<?php

// Switches

define('CRAYON_DEBUG', FALSE); // Enable to show exceptions on screen

// Constants

$uid = CRAYON_DEBUG ? uniqid() : ''; // Prevent caching in debug mode

define('CRAYON_VERSION', '1.0.2' . $uid);
define('CRAYON_DATE', '10th August, 2011');
define('CRAYON_AUTHOR', 'Aram Kocharyan');
// TODO These will be changed once I set up a site for docs
define('CRAYON_WEBSITE', 'http://ak.net84.net/?go=crayon');
define('CRAYON_WEBSITE_DOCS', 'http://ak.net84.net/?go=crayondocs');
define('CRAYON_EMAIL', 'crayon.syntax@gmail.com');
define('CRAYON_TWITTER', 'http://twitter.com/crayonsyntax');

// XXX Used to name the class

define('CRAYON_HIGHLIGHTER', 'CrayonHighlighter');
define('CRAYON_ELEMENT_CLASS', 'CrayonElement');
define('CRAYON_SETTING_CLASS', 'CrayonSetting');

// Directories

define('CRAYON_DIR', basename(dirname(__FILE__)) . crayon_slash());
define('CRAYON_LANG_DIR', crayon_slash('langs'));
define('CRAYON_THEME_DIR', crayon_slash('themes'));
define('CRAYON_FONT_DIR', crayon_slash('fonts'));
define('CRAYON_UTIL_DIR', crayon_slash('util'));
define('CRAYON_CSS_DIR', crayon_slash('css'));
define('CRAYON_JS_DIR', crayon_slash('js'));

// Paths

define('CRAYON_ROOT_PATH', dirname(__FILE__) . crayon_slash());
define('CRAYON_LANG_PATH', CRAYON_ROOT_PATH . CRAYON_LANG_DIR);
define('CRAYON_THEME_PATH', CRAYON_ROOT_PATH . CRAYON_THEME_DIR);
define('CRAYON_FONT_PATH', CRAYON_ROOT_PATH . CRAYON_FONT_DIR);
define('CRAYON_UTIL_PATH', CRAYON_ROOT_PATH . CRAYON_UTIL_DIR);

// Files

define('CRAYON_LOG_FILE', CRAYON_ROOT_PATH . 'log.txt');
define('CRAYON_TOUCH_FILE', CRAYON_UTIL_PATH . 'touch.txt');
define('CRAYON_LOG_MAX_SIZE', 50000); // Bytes

define('CRAYON_LANG_EXT', CRAYON_LANG_PATH . 'extensions.txt');
define('CRAYON_HELP_FILE', CRAYON_UTIL_PATH . 'help.htm');
define('CRAYON_JQUERY', CRAYON_JS_DIR . 'jquery-1.5.min.js');
define('CRAYON_JS', CRAYON_JS_DIR . 'crayon.js');
define('CRAYON_JS_ADMIN', CRAYON_JS_DIR . 'crayon_admin.js');
define('CRAYON_STYLE', CRAYON_CSS_DIR . 'style.css');
define('CRAYON_STYLE_ADMIN', CRAYON_CSS_DIR . 'admin_style.css');
define('CRAYON_LOGO', CRAYON_CSS_DIR . 'images/crayon_logo.png');

// PHP Files
define('CRAYON_FORMATTER_PHP', CRAYON_ROOT_PATH . 'crayon_formatter.class.php');
define('CRAYON_HIGHLIGHTER_PHP', CRAYON_ROOT_PATH . 'crayon_highlighter.class.php');
define('CRAYON_LANGS_PHP', CRAYON_ROOT_PATH . 'crayon_langs.class.php');
define('CRAYON_PARSER_PHP', CRAYON_ROOT_PATH . 'crayon_parser.class.php');
define('CRAYON_SETTINGS_PHP', CRAYON_ROOT_PATH . 'crayon_settings.class.php');
define('CRAYON_THEMES_PHP', CRAYON_ROOT_PATH . 'crayon_themes.class.php');
define('CRAYON_FONTS_PHP', CRAYON_ROOT_PATH . 'crayon_fonts.class.php');
define('CRAYON_RESOURCE_PHP', CRAYON_ROOT_PATH . 'crayon_resource.class.php');
define('CRAYON_UTIL_PHP', CRAYON_UTIL_DIR . 'crayon_util.class.php');
define('CRAYON_EXCEPTIONS_PHP', CRAYON_UTIL_DIR . 'exceptions.php');
define('CRAYON_TIMER_PHP', CRAYON_UTIL_DIR . 'crayon_timer.class.php');
define('CRAYON_LOG_PHP', CRAYON_UTIL_DIR . 'crayon_log.class.php');
define('CRAYON_LIST_LANGS_PHP', CRAYON_UTIL_DIR . 'list_langs.php');
define('CRAYON_PREVIEW_PHP', CRAYON_UTIL_DIR . 'preview.php');
define('CRAYON_AJAX_PHP', CRAYON_UTIL_DIR . 'ajax.php');

// Script time

define('CRAYON_LOAD_TIME', 'Load Time');
define('CRAYON_PARSE_TIME', 'Parse Time');
define('CRAYON_FORMAT_TIME', 'Format Time');

// Printing

define('CRAYON_BR', "<br />");
define('CRAYON_NL', "\r\n");
define('CRAYON_BL', CRAYON_BR . CRAYON_NL);
define('CRAYON_DASH', "==============================================================================");
define('CRAYON_LINE', "------------------------------------------------------------------------------");

// Load utilities

require_once (CRAYON_UTIL_PHP);
require_once (CRAYON_EXCEPTIONS_PHP);
require_once (CRAYON_TIMER_PHP);
require_once (CRAYON_LOG_PHP);

// Turn on the error & exception handlers

crayon_handler_on();
// Check current version from given file, not used, realised I was losing my mind

$crayon_version = NULL;

function crayon_version($file = NULL) {
	global $crayon_version, $uid;
	if ($file == NULL) {
		// Return current version

		if ($crayon_version == NULL) {
			// Fallback to unknown version

			$crayon_version = 'X' . $uid;
		}
	} else if (is_string($file) && file_exists($file)) {
		// Extract version from file

		$contents = @file_get_contents($file);
		if ($contents !== FALSE) {
			$pattern = '#<\\?php\\s*\\/\\*.*Version:\\s*([^\\s]*)\\r?\\n#smi';
			preg_match($pattern, $contents, $match);
			if (count($match) > 1) {
				$crayon_version = $match[1] . $uid;
			}
		}
	}
	return $crayon_version;
}

// Check for forwardslash/backslash in folder path to structure paths

$crayon_slash = NULL;

function crayon_slash($url = '') {
	global $crayon_slash;
	if ($crayon_slash == NULL) {
		if (strpos(dirname(__FILE__), '\\')) {
			$crayon_slash = '\\';
		} else {
			$crayon_slash = '/';
		}
	}
	return $url . $crayon_slash;
}

?>