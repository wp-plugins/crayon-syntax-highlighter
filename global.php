<?php

// Switches

define('CRAYON_DEBUG', FALSE); // Enable to show exceptions on screen

// Constants

$uid = CRAYON_DEBUG ? uniqid() : ''; // Prevent caching in debug mode

// These are overriden by functions since v1.1.1
$CRAYON_VERSION = '1.1.1' . $uid;
$CRAYON_DATE = '27th September, 2011';
$CRAYON_AUTHOR = 'Aram Kocharyan';
$CRAYON_WEBSITE = 'http://ak.net84.net/?go=crayon';
$CRAYON_WEBSITE_DOCS = 'http://ak.net84.net/?go=crayondocs';
$CRAYON_EMAIL = 'crayon.syntax@gmail.com';
$CRAYON_TWITTER = 'http://twitter.com/crayonsyntax';

// XXX Used to name the class

define('CRAYON_HIGHLIGHTER', 'CrayonHighlighter');
define('CRAYON_ELEMENT_CLASS', 'CrayonElement');
define('CRAYON_SETTING_CLASS', 'CrayonSetting');

// Directories

define('CRAYON_DIR', crayon_pf(basename(dirname(__FILE__))));
define('CRAYON_LANG_DIR', crayon_s('langs'));
define('CRAYON_THEME_DIR', crayon_s('themes'));
define('CRAYON_FONT_DIR', crayon_s('fonts'));
define('CRAYON_UTIL_DIR', crayon_s('util'));
define('CRAYON_CSS_DIR', crayon_s('css'));
define('CRAYON_JS_DIR', crayon_s('js'));

// Paths

define('CRAYON_ROOT_PATH', crayon_pf(dirname(__FILE__)));
define('CRAYON_LANG_PATH', CRAYON_ROOT_PATH . CRAYON_LANG_DIR);
define('CRAYON_THEME_PATH', CRAYON_ROOT_PATH . CRAYON_THEME_DIR);
define('CRAYON_FONT_PATH', CRAYON_ROOT_PATH . CRAYON_FONT_DIR);
define('CRAYON_UTIL_PATH', CRAYON_ROOT_PATH . CRAYON_UTIL_DIR);

// Files

define('CRAYON_LOG_FILE', CRAYON_ROOT_PATH . 'log.txt');
define('CRAYON_TOUCH_FILE', CRAYON_UTIL_PATH . 'touch.txt');
define('CRAYON_LOG_MAX_SIZE', 50000); // Bytes

define('CRAYON_README_FILE', CRAYON_ROOT_PATH . 'readme.txt');
define('CRAYON_LANG_EXT', CRAYON_LANG_PATH . 'extensions.txt');
define('CRAYON_HELP_FILE', CRAYON_UTIL_PATH . 'help.htm');
define('CRAYON_JQUERY', CRAYON_JS_DIR . 'jquery-1.5.min.js');
define('CRAYON_JQUERY_POPUP', CRAYON_JS_DIR . 'jquery.popup.js');
define('CRAYON_JS', CRAYON_JS_DIR . 'crayon.js');
define('CRAYON_JS_ADMIN', CRAYON_JS_DIR . 'crayon_admin.js');
define('CRAYON_STYLE', CRAYON_CSS_DIR . 'style.css');
define('CRAYON_STYLE_ADMIN', CRAYON_CSS_DIR . 'admin_style.css');
define('CRAYON_LOGO', CRAYON_CSS_DIR . 'images/crayon_logo.png');
define('CRAYON_DONATE_BUTTON', CRAYON_CSS_DIR . 'images/donate.png');

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

// GLOBAL FUNCTIONS

// Check for forwardslash/backslash in folder path to structure paths
function crayon_s($url = '') {
	$url = strval($url);
	if (!empty($url) && !preg_match('#(\\\\|/)$#', $url)) {
		return $url . '/';
	} else if ( empty($url) ) {
		return '/';
	} else {
		return $url;
	}
}

// Returns path using forward slashes
function crayon_pf($url) {
	return str_replace('\\', '/', crayon_s(trim(strval($url))));
}
	
// Returns path using back slashes
function crayon_pb($url) {
	return str_replace('/', '\\', crayon_s(trim(strval($url))));
}

// Get/Set plugin information
function set_crayon_info($info_array) {
	global $CRAYON_VERSION, $CRAYON_DATE, $CRAYON_AUTHOR, $CRAYON_WEBSITE, $uid;
	if (!is_array($info_array)) {
		return;
	}
	set_info('Version', $info_array, $CRAYON_VERSION);
	$CRAYON_VERSION .= $uid;
	if (($date = @filemtime(CRAYON_README_FILE)) !== FALSE) {
		$CRAYON_DATE = date("jS F, Y", $date);
	}
	set_info('AuthorName', $info_array, $CRAYON_A);
	set_info('PluginURI', $info_array, $CRAYON_WEBSITE);
}

function set_info($key, $array, &$info) {
	if (array_key_exists($key, $array)) {
		$info = $array[$key];
	} else {
		return FALSE;
	}
}

?>