<?php
require_once ('global.php');
require_once (CRAYON_PARSER_PHP);
require_once (CRAYON_THEMES_PHP);

/**
 * Stores CrayonSetting objects.
 * Each Crayon instance stores an instance of this class containing its specific settings.
 */
class CrayonSettings {
	// Properties and Constants ===============================================
	const INVALID = -1; // Used for invalid dropdown index
	// Plugin data
	const VERSION = 'version';

	// Global names for settings
	const THEME = 'theme';
	const FONT = 'font';
	const FONT_SIZE_ENABLE = 'font-size-enable';
	const FONT_SIZE = 'font-size';
	const PREVIEW = 'preview';
	const HEIGHT_SET = 'height-set';
	const HEIGHT_MODE = 'height-mode';
	const HEIGHT = 'height';
	const HEIGHT_UNIT = 'height-unit';
	const WIDTH_SET = 'width-set';
	const WIDTH_MODE = 'width-mode';
	const WIDTH = 'width';
	const WIDTH_UNIT = 'width-unit';
	const TOP_SET = 'top-set';
	const TOP_MARGIN = 'top-margin';
	const LEFT_SET = 'left-set';
	const LEFT_MARGIN = 'left-margin';
	const BOTTOM_SET = 'bottom-set';
	const BOTTOM_MARGIN = 'bottom-margin';
	const RIGHT_SET = 'right-set';
	const RIGHT_MARGIN = 'right-margin';
	const H_ALIGN = 'h-align';
	const FLOAT_ENABLE = 'float-enable';
	const TOOLBAR = 'toolbar';
	const TOOLBAR_OVERLAY = 'toolbar-overlay';
	const TOOLBAR_HIDE = 'toolbar-hide';
	const TOOLBAR_DELAY = 'toolbar-delay';
	const SHOW_LANG = 'show-lang';
	const SHOW_TITLE = 'show-title';
	const STRIPED = 'striped';
	const MARKING = 'marking';
	const NUMS = 'nums';
	const NUMS_TOGGLE = 'nums-toggle';
	const TRIM_WHITESPACE = 'trim-whitespace';
	const TAB_SIZE = 'tab-size';
	const FALLBACK_LANG = 'fallback-lang';
	const LOCAL_PATH = 'local-path';
	const SCROLL = 'scroll';
	const PLAIN = 'plain';
	const SHOW_PLAIN = 'show-plain';
	const DISABLE_RUNTIME = 'runtime';
	const EXP_SCROLL = 'exp-scroll';
	const TOUCHSCREEN = 'touchscreen';
	const DISABLE_ANIM = 'disable-anim';
	const ERROR_LOG = 'error-log';
	const ERROR_LOG_SYS = 'error-log-sys';
	const ERROR_MSG_SHOW = 'error-msg-show';
	const ERROR_MSG = 'error-msg';
	const HIDE_HELP = 'hide-help';
	
	// The current settings, should be loaded with default if none exists
	private $settings = array();
	
	// The settings with default values
	private static $default = NULL;

	function __construct() {
		$this->init();
	}
	
	function copy() {
		$settings = new CrayonSettings();
		foreach ($this->settings as $setting) {
			$settings->set($setting); // Overuse of set?
		}
		return $settings;
	}

	// Methods ================================================================

	private function init() {
		global $CRAYON_VERSION;
		$settings = array(
			new CrayonSetting(self::VERSION, $CRAYON_VERSION, NULL, TRUE),

			new CrayonSetting(self::THEME, CrayonThemes::DEFAULT_THEME), 
			new CrayonSetting(self::FONT, CrayonFonts::DEFAULT_FONT), 
			new CrayonSetting(self::FONT_SIZE_ENABLE, FALSE),
			new CrayonSetting(self::FONT_SIZE, 12), 
			new CrayonSetting(self::PREVIEW, TRUE),
			new CrayonSetting(self::HEIGHT_SET, FALSE), 
			new CrayonSetting(self::HEIGHT_MODE, array('Max', 'Min', 'Static')), 
			new CrayonSetting(self::HEIGHT, '500'), 
			new CrayonSetting(self::HEIGHT_UNIT, array('Pixels', 'Percent')), 
			new CrayonSetting(self::WIDTH_SET, FALSE), 
			new CrayonSetting(self::WIDTH_MODE, array('Max', 'Min', 'Static')), 
			new CrayonSetting(self::WIDTH, '500'), 
			new CrayonSetting(self::WIDTH_UNIT, array('Pixels', 'Percent')), 
			new CrayonSetting(self::TOP_SET, TRUE),
			new CrayonSetting(self::TOP_MARGIN, 12), 
			new CrayonSetting(self::BOTTOM_SET, TRUE),
			new CrayonSetting(self::BOTTOM_MARGIN, 12), 
			new CrayonSetting(self::LEFT_SET, FALSE),
			new CrayonSetting(self::LEFT_MARGIN, 12), 
			new CrayonSetting(self::RIGHT_SET, FALSE),
			new CrayonSetting(self::RIGHT_MARGIN, 12), 
			new CrayonSetting(self::H_ALIGN, array('None', 'Left', 'Center', 'Right')), 
			new CrayonSetting(self::FLOAT_ENABLE, FALSE), 
			new CrayonSetting(self::TOOLBAR, array('On MouseOver', 'Always', 'Never')), 
			new CrayonSetting(self::TOOLBAR_OVERLAY, TRUE),
			new CrayonSetting(self::TOOLBAR_HIDE, TRUE), 
			new CrayonSetting(self::TOOLBAR_DELAY, TRUE), 
			new CrayonSetting(self::SHOW_LANG, array('When Found', 'Always', 'Never')), 
			new CrayonSetting(self::SHOW_TITLE, TRUE),
			new CrayonSetting(self::STRIPED, TRUE), 
			new CrayonSetting(self::MARKING, TRUE),
			new CrayonSetting(self::NUMS, TRUE), 
			new CrayonSetting(self::NUMS_TOGGLE, TRUE),
			new CrayonSetting(self::TRIM_WHITESPACE, TRUE), 
			new CrayonSetting(self::TAB_SIZE, 4), 
			new CrayonSetting(self::FALLBACK_LANG, CrayonLangs::DEFAULT_LANG), 
			new CrayonSetting(self::LOCAL_PATH, ''), 
			new CrayonSetting(self::SCROLL, array('On MouseOver', 'Always')), 
			new CrayonSetting(self::PLAIN, TRUE), 
			new CrayonSetting(self::SHOW_PLAIN, 
					array('On Double Click', 'On Single Click', 'On MouseOver', 'Only Using Toggle')), 
			new CrayonSetting(self::DISABLE_ANIM, FALSE),
			new CrayonSetting(self::TOUCHSCREEN, TRUE), 
			new CrayonSetting(self::DISABLE_RUNTIME, FALSE),
			new CrayonSetting(self::EXP_SCROLL, FALSE), 
			new CrayonSetting(self::ERROR_LOG, TRUE),
			new CrayonSetting(self::ERROR_LOG_SYS, TRUE), 
			new CrayonSetting(self::ERROR_MSG_SHOW, TRUE), 
			new CrayonSetting(self::ERROR_MSG, 'An error has occurred. Please try again later.'),
			new CrayonSetting(self::HIDE_HELP, FALSE)
		);
		
		$this->set($settings);
	}

	// Getter and Setter ======================================================

	// TODO this needs simplification
	function set($name, $value = NULL, $replace = FALSE) {
		// Set associative array of settings
		if (is_array($name)) {
			$keys = array_keys($name);
			foreach ($keys as $key) {
				if ( is_string($key) ) {
					// Associative value
					$this->set($key, $name[$key], $replace);					
				} else if ( is_int($key) ) {
					$setting = $name[$key];
					$this->set($setting, NULL, $replace);
				}
			}
		} else if ( get_class($name) == CRAYON_SETTING_CLASS ) {
			$setting = $name; // Semantics
			if ( $replace || !$this->is_setting($setting->name()) ) {
				// Replace/Create
				$this->settings[$setting->name()] = $setting->copy();
			} else {
				// Update
				if ( $setting->is_array() ) {
					$this->settings[$setting->name()]->index($setting->index());
				} else {
					$this->settings[$setting->name()]->value($setting->value());
				}
			}
		} else if (is_string($name) && !empty($name) && $value !== NULL) {
			$value = CrayonSettings::validate($name, $value);
			if ($replace || !$this->is_setting($name)) {
				// Replace/Create
				$this->settings[$name] = new CrayonSetting($name, $value);
			} else {
				// Update
				$this->settings[$name]->value($value);
			}
		}
	}

	function get($name = NULL) {
		if ($name === NULL) {
			$copy = array();
			foreach ($this->settings as $name=>$setting) {
				$copy[$name] = $setting->copy(); // Deep copy
			}
			return $copy;
		} else if (is_string($name)) {
			if ($this->is_setting($name)) {
				return $this->settings[$name];
			}
		}
		return FALSE;
	}
	
	function val($name = NULL) {
		if (($setting = self::get($name)) != FALSE) {
			return $setting->value();
		} else {
			return NULL;
		}
	}
	
	function get_array() {
		$array = array();
		foreach ($this->settings as $setting) {
			$array[$setting->name()] = $setting->value();
		}
		return $array;
	}
	
	function is_setting($name) {
		return ( is_string($name) && array_key_exists($name, $this->settings) );
	}

	/* Gets default settings, either as associative array of name=>value or CrayonSetting
	 objects */
	public static function get_defaults($name = NULL, $objects = TRUE) {
		if (self::$default === NULL) {
			self::$default = new CrayonSettings();
		}
		if ($name === NULL) {
			// Get all settings
			if ($objects) {
				// Return array of objects
				return self::$default->get();
			} else {
				// Return associative array of name=>value
				$settings = self::$default->get();
				$defaults = array();
				foreach ($settings as $setting) {
					$defaults[$setting->name()] = $setting->value();
				}
				return $defaults;
			}
		} else {
			// Return specific setting
			if ($objects) {
				return self::$default->get($name);
			} else {
				return self::$default->get($name)->value();
			}
		}
	}

	public static function get_defaults_array() {
		return self::get_defaults(NULL, FALSE);
	}

	// Validation =============================================================

	/**
	 * Validates settings coming from an HTML form and also for internal use.
	 * This is used when saving form an HTML form to the db, and also when reading from the db
	 * back into the global settings.
	 * @param string $name
	 * @param mixed $value
	 */
	public static function validate($name, $value) {
		if (!is_string($name)) {
			return '';
		}
		
		// Type-cast to correct value for known settings
		if (($setting = CrayonGlobalSettings::get($name)) != FALSE) {
			// Booleans settings that are sent as string are allowed to have "false" == false
			if (is_string($value) && is_bool($setting->def())) {
				$value = trim(str_replace(array('no', 'false'), '0', $value));
			}
			// Ensure we don't cast integer settings to 0 because $value doesn't have any numbers in it
			if (is_string($value) && is_int($setting->def())) {
				// Only occurs when saving from the form ($_POST values are strings)
				if ($value == '' || ($cleaned = CrayonUtil::clean_int($value, FALSE)) == '') {
					// The value sent has no integers, change to default
					$value = $setting->def();
				} else {
					// Cleaned value is int
					$value = $cleaned;
				}
				$value = intval($value);
				// Cast all other settings as usual
			} else if (!settype($value, $setting->type())) {
				// If we can't cast, then use default value
				if ($setting->is_array()) {
					$value = 0; // default index
				} else {
					$value = $setting->def();
				}
			}
		} else {
			// If setting not found, remove value
			return '';
		}
		// Validations
		switch ($name) {
			case CrayonSettings::LOCAL_PATH:
				$path = parse_url($value, PHP_URL_PATH);
				// Remove all spaces, prefixed and trailing forward slashes
				$path = preg_replace('#^/*|/*$|\s*#', '', $path);
				// Replace backslashes
				$path = preg_replace('#\\\\#', '/', $path);
				// Append trailing forward slash
				if (!empty($path)) {
					$path .= '/';
				}
				return $path;
			case CrayonSettings::TAB_SIZE:
				$value = abs($value);
				break;
			case CrayonSettings::THEME:
				$value = strtolower($value);
			// XXX validate settings here
		}
		
		// If no validation occurs, return value
		return $value;
	}
	
	// Takes an associative array of "smart settings" and regular settings. Smart settings can be used
	// to configure regular settings quickly.
	// E.g. 'max_height="20px"' will set 'height="20"', 'height_mode="0", height_unit="0"'
	public static function smart_settings($settings) {
		if (!is_array($settings)) {
			return FALSE;
		}
		
		// If a setting is given, it is automatically enabled
		foreach ($settings as $name=>$value) {
			if ($name == 'min-height' || $name == 'max-height' || $name == 'height') {
				self::smart_hw($name, CrayonSettings::HEIGHT_SET, CrayonSettings::HEIGHT_MODE, CrayonSettings::HEIGHT_UNIT, $settings);
			} else if ($name == 'min-width' || $name == 'max-width' || $name == 'width') {
				self::smart_hw($name, CrayonSettings::WIDTH_SET, CrayonSettings::WIDTH_MODE, CrayonSettings::WIDTH_UNIT, $settings);
			} else if ($name == CrayonSettings::FONT_SIZE) {
				$settings[CrayonSettings::FONT_SIZE_ENABLE] = TRUE; 
			} else if ($name == CrayonSettings::TOP_MARGIN) {
				$settings[CrayonSettings::TOP_SET] = TRUE;
			} else if ($name == CrayonSettings::LEFT_MARGIN) {
				$settings[CrayonSettings::LEFT_SET] = TRUE;
			} else if ($name == CrayonSettings::BOTTOM_MARGIN) {
				$settings[CrayonSettings::BOTTOM_SET] = TRUE;
			} else if ($name == CrayonSettings::RIGHT_MARGIN) {
				$settings[CrayonSettings::RIGHT_SET] = TRUE;
			} else if ($name == CrayonSettings::H_ALIGN) {
				$settings[CrayonSettings::FLOAT_ENABLE] = TRUE;
			} else if ($name == CrayonSettings::H_ALIGN) {
				$settings[CrayonSettings::FLOAT_ENABLE] = TRUE;
			}
		}
		
		return $settings;
	}
	
	// Used for height and width smart settings, I couldn't bear to copy paste code twice...
	private static function smart_hw($name, $set, $mode, $unit, &$settings) {
		if (!is_string($name) || !is_string($set) || !is_string($mode) || !is_string($unit) || !is_array($settings)) {
			return;
		}
		$settings[$set] = TRUE;
		if ( strpos($name, 'max-') !== FALSE ) {
			$settings[$mode] = 0;
		} else if ( strpos($name, 'min-') !== FALSE ) {
			$settings[$mode] = 1;
		} else {
			$settings[$mode] = 2;
		}
		preg_match('#(\d+)\s*([^\s]*)#', $settings[$name], $match);
		if ( count($match) == 3 ) {
			$name = str_replace(array('max-', 'min-'), '', $name);
			$settings[$name] = $match[1];
			switch (strtolower($match[2])) {
				case 'px':
					$settings[$unit] = 0;
					break;
				case '%':
					$settings[$unit] = 1;
					break;
			}
		}
	}
}

/**
 * Stores global/static copy of CrayonSettings loaded from db.
 * These settings can be overriden by individual Crayons.
 * Also manages global site settings and paths.
 */
class CrayonGlobalSettings {
	// The global settings stored as a CrayonSettings object.
	private static $global = NULL;
	/* These are used to load local files reliably and prevent scripts like PHP from executing
	 when attempting to load their code. */
	// The URL of the site (eg. http://localhost/example/)
	private static $site_http = '';
	// The absolute root directory of the site (eg. /User/example/)
	private static $site_path = '';
	// The absolute root directory of the plugins (eg. /User/example/plugins)
	private static $plugin_path = '';
	private function __construct() {}

	private static function init() {
		if (self::$global === NULL) {
			self::$global = new CrayonSettings();
		}
	}

	public static function get($name = NULL) {
		self::init();
		return self::$global->get($name);
	}
	
	public static function get_array() {
		self::init();
		return self::$global->get_array();
	}

	public static function get_obj() {
		self::init();
		return self::$global->copy();
	}

	public static function val($name = NULL) {
		return self::$global->val($name);
	}

	public static function set($name, $value = NULL, $replace = FALSE) {
		self::init();
		self::$global->set($name, $value, $replace);
	}

	public static function site_http($site_http = NULL) {
		if (!CrayonUtil::str(self::$site_http, CrayonUtil::url_slash($site_http))) {
			return self::$site_http;
		}
	}

	public static function site_path($site_path = NULL) {
		if ($site_path === NULL) {
			return self::$site_path;
		} else {
			self::$site_path = CrayonUtil::url_slash($site_path);
		}
	}

	public static function plugin_path($plugin_path = NULL) {
		if ($plugin_path === NULL) {
			return self::$plugin_path;
		} else {
			self::$plugin_path = CrayonUtil::url_slash($plugin_path);
		}
	}
}

/**
 * Individual setting.
 * Can store boolean, string, dropdown (with array of strings), etc.
 */
class CrayonSetting {
	private $name = '';
	/* The type of variables that can be set as the value.
	 * For dropdown settings, value is int, even though value() will return a string. */
	private $type = NULL;
	private $default = NULL; // stores string array for dropdown settings

	private $value = NULL; // stores index int for dropdown settings

	private $is_array = FALSE; // only TRUE for dropdown settings
	private $locked = FALSE;


	public function __construct($name, $default = '', $value = NULL, $locked = NULL) {
		$this->name($name);
		if ($default !== NULL) {
			$this->def($default); // Perform first to set type

		}
		if ($value !== NULL) {
			$this->value($value);
		}
		if ($locked !== NULL) {
			$this->locked($locked);
		}
	}

	function __tostring() {
		return $this->name;
	}
	
	function copy() {
		return new CrayonSetting($this->name, $this->default, $this->value, $this->locked);
	}

	function name($name = NULL) {
		if (!CrayonUtil::str($this->name, $name)) {
			return $this->name;
		}
	}

	function type() {
		return $this->type;
	}

	function is_array() {
		return $this->is_array;
	}
	
	function locked($locked = NULL) {
		if ($locked === NULL) {
			return $this->locked;
		} else {
			$this->locked = ($locked == TRUE);
		}
	}

	/**

	 * Sets/gets value;
	 * Value is index (int) in default value (array) for dropdown settings.
	 * value($value) is alias for index($index) if dropdown setting.
	 * value() returns string value at current index for dropdown settings. 
	 * @param $value
	 */
	function value($value = NULL) {
		if ($value === NULL) {
			if ($this->is_array) {
				return $this->default[$this->value]; // value at index
			} else if ($this->value !== NULL) {
				return $this->value;
			} else {
				return $this->default;
			}
		} else if ($this->locked === FALSE) {
			if ($this->is_array) {
				$this->index($value); // $value is index
			} else {
				settype($value, $this->type); // Type case
				$this->value = $value;
			}
		}
	}

	/**
	 * Sets/gets default value.
	 * For dropdown settings, default value is array of all possible value strings.
	 * @param $default
	 */
	function def($default = NULL) {
		// Only allow default to be set once

		if ($this->type === NULL && $default !== NULL) {
			// For dropdown settings

			if (is_array($default)) { // The only time we don't use $this->is_array

				// If empty, set to blank array

				if (empty($default)) {
					$default = array('');
				} else {
					// Ensure all values are unique strings

					$default = CrayonUtil::array_unique_str($default);
				}
				$this->value = 0; // initial index

				$this->is_array = TRUE;
				$this->type = gettype(0); // Type is int (index)

			} else {
				$this->is_array = FALSE;
				$this->type = gettype($default);
			}
			$this->default = $default;
		} else {
			return $this->default;
		}
	}

	/**
	 * Sets/gets index. 
	 * @param int|string $index
	 * @return FALSE if not dropdown setting
	 */
	function index($index = NULL) {
		if (!$this->is_array) {
			return FALSE;
		} else if ($index === NULL) {
			return $this->value; // return current index
		} else {
			if (!is_int($index)) {
				// Ensure $value is int for index
				$index = intval($index);
			}
			// Validate index
			if ($index < 0 || $index > count($this->default) - 1) {
				$index = 0;
			}
			$this->value = $index;
		}
	}

}

?>