<?php
// Class includes
require_once ('global.php');
require_once (CRAYON_PARSER_PHP);
require_once (CRAYON_FORMATTER_PHP);
require_once (CRAYON_SETTINGS_PHP);
require_once (CRAYON_LANGS_PHP);

/* The main class for managing the syntax highlighter */
class CrayonHighlighter {
	// Properties and Constants ===============================================
	private $url = '';
	private $code = '';
	private $formatted_code = '';
	private $title = '';
	private $line_count = 0;
	private $marked_lines = array();
	private $error = '';
	// Determine whether the code needs to be loaded, parsed or formatted
	private $needs_load = TRUE;
	private $needs_parse = TRUE;
	private $needs_format = TRUE;
	// Record the script run times
	private $runtime = array();
	
	// Objects
	// Stores the CrayonLang being used
	private $language = NULL;
	// A copy of the current global settings which can be overridden
	private $settings = NULL;
	
	// Methods ================================================================
	function __construct($url = NULL, $language = NULL) {
		if ($url !== NULL) {
			$this->url($url);
		}
		if ($language !== NULL) {
			$this->language($language);
		}
	}
	
	/* Tries to load the code locally, then attempts to load it remotely */
	private function load() {
		if (empty($this->url)) {
			$this->error('The specified URL is empty, please provide a valid URL.');
			return;
		}
		/*	Try to replace the URL with an absolute path if it is local, used to prevent scripts
		 from executing when they are loaded. */
		$url = $this->url;
		$url = CrayonUtil::pathf($url);
		$local = FALSE; // Whether to read locally
		$site_http = CrayonGlobalSettings::site_http();
		$site_path = CrayonGlobalSettings::site_path();
		$scheme = parse_url($url, PHP_URL_SCHEME);
		
		// Try to replace the site URL with a path to force local loading
		if (strpos($url, $site_http) !== FALSE || strpos($url, $site_path) !== FALSE ) {
			$url = str_replace($site_http, $site_path, $url);
			// Attempt to load locally
			$local = TRUE;
			$local_url = $url;
		} else if (empty($scheme)) {
			// No url scheme is given - path may be given as relative
			$local_url = preg_replace('#^((\/|\\\\)*)?#', $site_path . $this->setting_val(CrayonSettings::LOCAL_PATH), $url);
			$local = TRUE;
		}
		// Try to find the file locally
		if ($local == TRUE) {
			if ( ($contents = CrayonUtil::file($local_url)) !== FALSE ) {
				$this->code($contents);
			} else {
				$local = FALSE;
				CrayonLog::log("Local url ($local_url) could not be loaded", '', FALSE);
			}
		}
		// If reading the url locally produced an error or failed, attempt remote request
		if ($local == FALSE) {
			if (empty($scheme)) {
				$url = 'http://' . $url;
			}
			$http_code = 0;
			if (function_exists('wp_remote_get')) {
				// If available, use the built in wp remote http get function, we don't need SSL
				$response = wp_remote_get($url, array('sslverify' => false));
				$content = wp_remote_retrieve_body($response);
				$http_code = wp_remote_retrieve_response_code($response);
			} else {
				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_HEADER, FALSE);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
				// For https connections, we do not require SSL verification
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
				curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
				curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
				$content = curl_exec($ch);
				$error = curl_error($ch);
				$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);
			}
			if ($http_code >= 200 && $http_code < 400) {
				$this->code = $content;
			} else {
				if (empty($this->code)) {
					// If code is also given, just use that
					$this->error("The provided URL ('$this->url') could not be accessed locally or remotely.");
				}
			}
		}
		$this->needs_load = FALSE;
	}

	/* Central point of access for all other functions to update code. */
	private function process($highlight = TRUE) {
		$tmr = new CrayonTimer();
		$this->runtime = NULL;
		if ($this->needs_load) {
			$tmr->start();
			$this->load();
			$this->runtime[CRAYON_LOAD_TIME] = $tmr->stop();
		}
		if (!empty($this->error) || empty($this->code)) {
			// Disable highlighting for errors and empty code
			return;
		}
		// Build the syntax regex, elements and regex are passed by reference
		if ($this->needs_parse) {
			$tmr->start();
			if (empty($this->language)) {
				$this->language($this->setting_val(CrayonSettings::FALLBACK_LANG));
			}
			
			if ( $this->language != NULL && !$this->language->is_default() && !$this->language->is_parsed()) {
				CrayonParser::parse($this->language()->id());
			}
			$this->needs_parse = FALSE;
			$this->runtime[CRAYON_PARSE_TIME] = $tmr->stop();
		}
		if ($this->needs_format) {
			$tmr->start();
			try {
				// Format the code with the generated regex and elements
				$this->formatted_code = CrayonFormatter::format_code($this->code, $this->language, $highlight, $this);
			} catch (Exception $e) {
				$this->error($e->message());
				return;
			}
			$this->needs_format = FALSE;
			$this->runtime[CRAYON_FORMAT_TIME] = $tmr->stop();
		}
	}

	/* Sends the code to the formatter for printing. Apart from the getters and setters, this is
	 the only other function accessible outside this class. $show_lines can also be a string. */
	function output($highlight = TRUE, $show_lines = TRUE, $print = TRUE) {
		$this->process($highlight);
		if (empty($this->error)) {
			// If no errors have occured, print the formatted code
			$ret = CrayonFormatter::print_code($this, $this->formatted_code, $show_lines, $print);
		} else {
			$ret = CrayonFormatter::print_error($this, $this->error, /*'ERROR'*/'', $print);
		}
		// Reset the error message at the end of the print session
		$this->error = '';
		// If $print = FALSE, $ret will contain the output
		return $ret;
	}

	// Getters and Setters ====================================================
	function code($code = NULL) {
		if ($code === NULL) {
			return $this->code;
		} else {
			// Trim whitespace
			if ($this->setting_val(CrayonSettings::TRIM_WHITESPACE)) {
				$code = preg_replace("#(?:^\\s*\\r?\\n)|(?:\\r?\\n\\s*$)#", '', $code);
			}
			$this->code = $code;
			$this->needs_load = FALSE; // No need to load, code provided
			$this->needs_format = TRUE;
		}
	}

	function language($id = NULL) {
		if ($id === NULL || !is_string($id)) {
			return $this->language;
		}
		
		if ( ($lang = CrayonResources::langs()->get($id)) != FALSE ) {
			// Set the language if it exists
			$this->language = $lang;
		} else {
			// Attempt to detect the language
			if (!empty($id)) {
				$this->log("The language '$id' could not be loaded.");
			}
			$this->language = CrayonResources::langs()->detect($this->url, $this->setting_val(CrayonSettings::FALLBACK_LANG));
		}
	}

	function url($url = NULL) {
		if (CrayonUtil::str($this->url, $url)) {
			$this->needs_load = TRUE;
		} else {
			return $this->url;
		}
	}

	function title($title = NULL) {
		if (!CrayonUtil::str($this->title, $title)) {
			return $this->title;
		}
	}

	function line_count($line_count = NULL) {
		if (!CrayonUtil::num($this->line_count, $line_count)) {
			return $this->line_count;
		}
	}

	function marked($str = NULL) {
		if ($str === NULL) {
			return $this->marked_lines;
		}
		// If only an int is given
		if (is_int($str)) {
			$array = array($str);
			return CrayonUtil::arr($this->marked_lines, $array);
		}
		// A string with ints separated by commas, can also contain ranges
		$array = CrayonUtil::trim_e($str);
		$array = array_unique($array);
		$lines = array();
		foreach ($array as $line) {
			// Check for ranges
			if (strpos($line, '-')) {
				$ranges = CrayonUtil::range_str($str);
				$lines = array_merge($lines, $ranges);
			} else {
				// Otherwise check the string for a number
				$line = CrayonUtil::clean_int($line);
				if ($line !== FALSE) {
					$lines[] = $line;
				}
			}
		}
		return CrayonUtil::arr($this->marked_lines, $lines);
	}

	function log($var) {
		if ($this->setting_val(CrayonSettings::ERROR_LOG)) {
			CrayonLog::log($var);
		}
	}

	function error($string = NULL) {
		if (!$string) {
			return $this->error;
		}
		$this->error .= $string;
		$this->log($string);
		// Add the error string and ensure no further processing occurs
		$this->needs_load = FALSE;
		$this->needs_parse = FALSE;
		$this->needs_format = FALSE;
	}

	// Set and retreive settings
	function settings($mixed = NULL) {
		if ($this->settings == NULL) {
			$this->settings = CrayonGlobalSettings::get_obj();
		}
		
		if ($mixed === NULL) {
			return $this->settings;
		} else if (is_string($mixed)) {
			return $this->settings->get($mixed);
		} else if (is_array($mixed)) {
			$this->settings->set($mixed);
			
			return TRUE;
		}
		return FALSE;
	}

	/* Retrieve a single setting's value for use in the formatter. By default, on failure it will
	 * return TRUE to ensure FALSE is only sent when a setting is found. This prevents a fake
	 * FALSE when the formatter checks for a positive setting (Show/Enable) and fails. When a
	 * negative setting is needed (Hide/Disable), $default_return should be set to FALSE. */
	function setting_val($name = NULL, $default_return = TRUE) {
		if (is_string($name) && $setting = $this->settings($name)) {
			return $setting->value();
		} else {
			// Name not valid
			return (is_bool($default_return) ? $default_return : TRUE);
		}
	}

	// Used to find current index in dropdown setting
	function setting_index($name = NULL) {
		$setting = $this->settings($name);
		if (is_string($name) && $setting->is_array()) {
			return $setting->index();
		} else {
			// Returns -1 to avoid accidentally selecting an item in a dropdown
			return CrayonSettings::INVALID;
		}
	}

	function formatted_code() {
		return $this->formatted_code;
	}

	function runtime() {
		return $this->runtime;
	}
}
?>