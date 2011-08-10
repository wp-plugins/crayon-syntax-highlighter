<?php

/* Common utility functions mainly for formatting, parsing etc. */
class CrayonUtil {
	// Used to detect touchscreen devices
	private static $touchscreen = NULL;

	/* Return the lines inside a file as an array, options:
	 l - lowercase
	 w - remove whitespace
	 r - escape regex chars
	 c - remove comments
	 s - return as string */
	public static function lines($path, $opts = NULL) {
		if ( ($str = self::file($path)) === FALSE ) {
			// Log failure, n = no log
			if ( strpos($opts, 'n') === FALSE ) {
				CrayonLog::syslog("Cannot read lines at '$path'.", "CrayonUtil::lines()");
			}
			return FALSE;
		}
		// Read the options
		if (is_string($opts)) {
			$lowercase = strpos($opts, 'l') !== FALSE;
			$whitespace = strpos($opts, 'w') !== FALSE;
			$escape_regex = strpos($opts, 'r') !== FALSE;
			$clean_commments = strpos($opts, 'c') !== FALSE;
			$return_string = strpos($opts, 's') !== FALSE;
			$escape_hash = strpos($opts, 'h') !== FALSE;
		} else {
			$lowercase = $whitespace = $escape_regex = $clean_commments = $return_string = $escape_hash = FALSE;
		}
		// Remove comments
		if ($clean_commments) {
			$str = self::clean_comments($str);
		}
		
		// Convert to lowercase if needed
		if ($lowercase) {
			$str = strtolower($str);
		}
		/*  Match all the content on non-empty lines, also remove any whitespace to the left and
		 right if needed */
		if ($whitespace) {
			$pattern = '[^\s]+(?:.*[^\s])?';
		} else {
			$pattern = '^(?:.*)?';
		}
		
		preg_match_all('|' . $pattern . '|m', $str, $matches);
		$lines = $matches[0];
		// Remove regex syntax and assume all characters are literal
		if ($escape_regex) {
			for ($i = 0; $i < count($lines); $i++) {
				$lines[$i] = self::esc_regex($lines[$i]);
				if ($escape_hash || true) {
					// If we have used \#, then we don't want it to become \\#
					$lines[$i] = preg_replace('|\\\\\\\\#|', '\#', $lines[$i]);
				}
			}
		}
		
		// Return as string if needed
		if ($return_string) {
			// Add line breaks if they were stripped
			$delimiter = '';
			if ($whitespace) {
				$delimiter = CRAYON_NL;
			}
			$lines = implode($lines, $delimiter);
		}
		
		return $lines;
	}

	// Returns the contents of a file
	public static function file($path) {
		if ( ($str = @file_get_contents($path)) === FALSE ) {
			return FALSE;
		} else {
			return $str;
		}
	}

	// Detects if device is touchscreen or mobile
	public static function is_touch() {
		// Only detect once
		if (self::$touchscreen !== NULL) {
			return self::$touchscreen;
		}
		if ( ($devices = CrayonUtil::lines(CRAYON_TOUCH_FILE, 'lw')) !== FALSE ) {
			// Create array of device strings from file
			$user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
			self::$touchscreen = (CrayonUtil::strposa($user_agent, $devices) !== FALSE);
			return self::$touchscreen;
		} else {
			CrayonLog::syslog('Error occurred when trying to identify touchscreen devices');
		}
	}

	// Removes duplicates in array, ensures they are all strings
	public static function array_unique_str($array) {
		if (!is_array($array) || empty($array)) {
			return array();
		}
		for ($i = 0; $i < count($array); $i++) {
			$array[$i] = strval($array[$i]);
		}
		return array_unique($array);
	}
	
	// Same as array_key_exists, but returns the key when exists, else FALSE;
	public static function array_key_exists($key, $array) {
		if (!is_array($array) || empty($array) || !is_string($key) || empty($key)) {
			FALSE;
		}
		if ( array_key_exists($key, $array) ) {
			return $array[$key];
		}
	}

	// Performs explode() on a string with the given delimiter and trims all whitespace
	public static function trim_e($str, $delimiter = ',') {
		if (is_string($delimiter)) {
			$str = trim(preg_replace('|\s*(?:' . preg_quote($delimiter) . ')\s*|', $delimiter, $str));
			return explode($delimiter, $str);
		}
		return $str;
	}

	/*  Creates an array of integers based on a given range string of format "int - int"
	 Eg. range_str('2 - 5'); */
	public static function range_str($str) {
		preg_match('#(\d+)\s*-\s*(\d+)#', $str, $matches);
		if (count($matches) == 3) {
			return range($matches[1], $matches[2]);
		}
		return FALSE;
	}

	// Sets a variable to a string if valid
	public static function str(&$var, $str, $escape = TRUE) {
		if (is_string($str)) {
			$var = ($escape == TRUE ? htmlentities($str) : $str);
			return TRUE;
		}
		return FALSE;
	}

	// Sets a variable to an int if valid
	public static function num(&$var, $num) {
		if (is_numeric($num)) {
			$var = intval($num);
			return TRUE;
		}
		return FALSE;
	}

	// Sets a variable to an array if valid
	public static function arr(&$var, $array) {
		if (is_array($array)) {
			$var = $array;
			return TRUE;
		}
		return FALSE;
	}
	
	// Thanks, http://www.php.net/manual/en/function.str-replace.php#102186
	function str_replace_once($str_pattern, $str_replacement, $string){ 
        if (strpos($string, $str_pattern) !== FALSE){ 
            $occurrence = strpos($string, $str_pattern); 
            return substr_replace($string, $str_replacement, strpos($string, $str_pattern), strlen($str_pattern)); 
        }
        return $string; 
    }

	// Removes non-numeric chars in string
	public static function clean_int($str, $return_zero = TRUE) {
		$str = preg_replace('#[^\d]#', '', $str);
		if ($return_zero) {
			// If '', then returns 0
			return strval(intval($str));
		} else {
			// Might be ''
			return $str;
		}
	}

	// Removes non-alphanumeric chars in string, replaces spaces with hypthen, makes lowercase
	public static function clean_css_name($str) {
		$str = preg_replace('#[^\w\s]#', '', $str);
		$str = preg_replace('#\s+#', '-', $str);
		return strtolower($str);
	}

	// Remove comments with /* */, // or #, if they occur before any other char on a line
	public static function clean_comments($str) {
		$comment_pattern = '#(?:^\s*/\*.*?^\s*\*/)|(?:^(?!\s*$)[\s]*(?://|\#)[^\r\n]*)#ms';
		$str = preg_replace($comment_pattern, '', $str);
		return $str;
	}

	// Convert to title case and replace underscores with spaces 
	public static function ucwords($str) {
		$str = strval($str);
		$str = str_replace('_', ' ', $str);
		return ucwords($str);
	}

	// Escapes regex characters as literals
	public static function esc_regex($regex) {
		return /*htmlspecialchars(*/preg_quote($regex)/* , ENT_NOQUOTES)*/;
	}
	
	// Escapes regex characters as literals
	public static function esc_hash($regex) {
		if (is_string($regex)) {
			return preg_replace('|(?<!\\\\)#|', '\#', $regex);
		} else {
			return FALSE;
		}
	}

	// Removes crayon plugin path from absolute path
	public static function path_rel($url) {
		if (is_string($url)) {
			return str_replace(CRAYON_ROOT_PATH, crayon_slash(), $url);
		}
		return $url;
	}

	// Returns path according to detected use of forwardslash/backslash
	public static function path($path, $detect) {
		if (strpos($detect, '\\')) {
			// Windows
			$slash = '\\';
		} else {
			// UNIX
			$slash = '/';
		}
		return str_replace(array('\\', '/'), $slash, $path);
	}

	// Returns path using forward slashes
	public static function path_f_slash($url) {
		return str_replace('\\', '/', $url);
	}

	// Append either forward slash or backslash based on environment to paths
	public static function path_slash($path) {
		$path = strval($path);
		if (!empty($path) && !preg_match('#\\\\|/$#', $path)) {
			$path .= crayon_slash();
		}
		return $path;
	}

	// Append a forward slash to a path if needed
	public static function url_slash($url) {
		$url = trim(strval($url));
		if (!empty($url) && !preg_match('#(/|\\\\)$#', $url)) {
			$url .= '/';
		}
		return $url;
	}

	// Removes extension from file path
	public static function path_rem_ext($path) {
		$path = strval($path);
		return preg_replace('#\.\w+$#m', '', $path);
	}

	// strpos with an array of $needles
	public static function strposa($haystack, $needles) {
		if (is_array($needles)) {
			foreach ($needles as $str) {
				if (is_array($str)) {
					$pos = CrayonUtil::strposa($haystack, $str);
				} else {
					$pos = strpos($haystack, $str);
				}
				if ($pos !== FALSE) {
					return $pos;
				}
			}
			return FALSE;
		} else {
			return strpos($haystack, $needles);
		}
	}

	// tests if $needle is equal to any strings in $haystack
	public static function str_equal_array($needle, $haystack, $case_insensitive = TRUE) {
		if (!is_string($needle) || !is_array($haystack)) {
			return FALSE;
		}
		if ($case_insensitive) {
			$needle = strtolower($needle);
		}
		foreach ($haystack as $hay) {
			if (!is_string($hay)) {
				continue;
			}
			if ($case_insensitive) {
				$hay = strtolower($hay);
			}
			if ($needle == $hay) {
				return TRUE;
			}
		}
		return FALSE;
	}

	// Support for singular and plural string variations
	public static function spnum($int, $singular, $plural = NULL) {
		if (!is_int($int) || !is_string($singular)) {
			$int = intval($int);
			$singular = strval($singular);
		}
		if ($plural == NULL || !is_string($plural)) {
			$plural = $singular . 's';
		}
		return $int . ' ' . (($int == 1) ? $singular : $plural);
	}

	// Turn boolean into Yes/No
	public static function bool_yn($bool) {
		return $bool ? 'Yes' : 'No';
	}

	// Decodes WP html entities
	public static function html_entity_decode_wp($str) {
		if (!is_string($str) || empty($str)) {
			return $str;
		}
		// http://www.ascii.cl/htmlcodes.htm
		$wp_entities = array('&#8216;', '&#8217;', '&#8218;', '&#8220;', '&#8221;');
		$wp_replace = array('\'', '\'', ',', '"', '"');
		$str = str_replace($wp_entities, $wp_replace, $str);
		return $str;
	}
}
?>