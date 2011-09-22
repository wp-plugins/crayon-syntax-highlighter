<?php
require_once ('global.php');
require_once (CRAYON_LANGS_PHP);
require_once (CRAYON_THEMES_PHP);
require_once (CRAYON_FONTS_PHP);

class CrayonResources {
	private static $langs = NULL;
	private static $themes = NULL;
	private static $fonts = NULL;

	private function __construct() {}

	private static function init() {
		if (self::$langs == NULL) {
			self::$langs = new CrayonLangs();
		}
		if (self::$themes == NULL) {
			self::$themes = new CrayonThemes();
		}
		if (self::$fonts == NULL) {
			self::$fonts = new CrayonFonts();
		}
	}

	public static function langs() {
		self::init();
		return self::$langs;
	}

	public static function themes() {
		self::init();
		return self::$themes;
	}

	public static function fonts() {
		self::init();
		return self::$fonts;
	}
}

class CrayonResourceCollection {
	// Properties and Constants ===============================================

	// Loaded resources

	private $collection = array();
	// Loading state

	private $state = self::UNLOADED;
	// Directory containing resources

	private $dir = '';
	private $default_id = '';
	private $default_name = '';
	const UNLOADED = -1;
	const LOADING = 0;
	const LOADED = 1;

	// Methods ================================================================

	/* Override in subclasses. Returns the absolute path for a given resource. Does not check for its existence. */
	public function path($id) {
		return '';
	}

	/* Verifies a language exists. */
	public function exists($id) {
		return file_exists($this->path($id));
	}

	/* Load all the available languages. Doesn't parse them for their names and regex. */
	public function load() {
		// Load only once

		if (!$this->is_state_unloaded()) {
			return;
		}
		$this->state = self::LOADING;
		$this->load_process();
		$this->state = self::LOADED;
	}

	public function load_resources() {
		// Load only once

		if (!$this->is_state_loading()) {
			return;
		}
		try {
			// Look in directory for resources

			if (!file_exists($this->dir)) {
				CrayonLog::syslog('The resource directory is missing, should be at \'' . $this->dir . '\'.');
			} else if (($handle = @opendir($this->dir)) != FALSE) {
				// Loop over directory contents

				while (($file = readdir($handle)) !== FALSE) {
					if ($file != "." && $file != "..") {
						// Check if $file is directory, remove extension when checking for existence.

						if (!is_dir($this->dir . $file)) {
							$file = CrayonUtil::path_rem_ext($file);
						}
						if ($this->exists($file)) {
							$this->add($file, $this->resource_instance($file));
						}
					}
				}
				closedir($handle);
			}
		} catch (Exception $e) {
			CrayonLog::syslog('An error occured when trying to load resources: ' . $e->getFile() . $e->getLine());
		}
	}

	/* Override in subclasses. */
	public function load_process() {
		if (!$this->is_state_loading()) {
			return;
		}
		$this->load_resources();
		$this->add_default();
	}

	/* Override in subclasses */
	public function add_default() {
		if (!$this->is_state_loading()) {
			return FALSE;
		} else if (!$this->is_loaded($this->default_id)) {
			CrayonLog::syslog('The default resource could not be loaded from \'' . $this->dir . '\'.');
			// Add the default, but it will not be functionable

			$default = $this->resource_instance($this->default_id, $this->default_name);
			$this->add($this->default_id, $default);
			return TRUE;
		}
		return FALSE;
	}

	/* Returns the default resource */
	public function set_default($id, $name) {
		$this->default_id = $id;
		$this->default_name = $name;
	}

	/* Returns the default resource */
	public function get_default() {
		return $this->get($this->default_id);
	}

	/* Override in subclasses */
	public function resource_instance($id, $name = NULL) {
		return new CrayonResource($id, $name);
	}

	public function add($name, $object) {
		if (is_string($name) && !empty($name)) {
			$this->collection[strtolower(trim($name))] = $object;
			asort($this->collection);
		}
	}

	public function remove($name) {
		if (is_string($name) && !empty($name) && array_key_exists($name, $this->collection)) {
			unset($this->collection[$name]);
		}
	}

	public function remove_all() {
		$this->collection = array();
	}

	/* Returns the resource for the given id or NULL if it can't be found */
	public function get($id = NULL) {
		$this->load();
		if ($id === NULL) {
			return $this->collection;
		} else if (is_string($id) && ($id = strtolower(trim($id))) !== FALSE && $this->is_loaded($id)) {
			return $this->collection[$id];
		}
		return NULL;
	}

	public function is_loaded($id) {
		if (is_string($id)) {
			return array_key_exists($id, $this->collection);
		}
		return FALSE;
	}

	public function get_state() {
		return $this->state;
	}

	public function is_state_loaded() {
		return $this->state == self::LOADED;
	}

	public function is_state_loading() {
		return $this->state == self::LOADING;
	}

	public function is_state_unloaded() {
		return $this->state == self::UNLOADED;
	}

	public function directory($dir = NULL) {
		$dir = CrayonUtil::path_slash($dir);
		if (!CrayonUtil::str($this->dir, $dir, FALSE)) {
			return $this->dir;
		}
	}
}

class CrayonUsedResourceCollection extends CrayonResourceCollection {

	// Checks if any of the themes are being used
	public function is_used($id = NULL) {
		if ($id === NULL) {
			foreach ($this->get() as $resource) {
				if ($resource->used()) {
					return TRUE;
				}
			}
			return FALSE;
		} else {
			$resource = $this->get($id);
			if (!$resource) {
				return FALSE;
			} else {
				return $resource->used();
			}
		}
	}
	
	// XXX Override
	public function resource_instance($id, $name = NULL) {
		return new CrayonUsedResource($id, $name);
	}
}

class CrayonResource {
	private $id = '';
	private $name = '';

	function __construct($id, $name = NULL) {
		CrayonUtil::str($this->id, $id);
		( empty($name) ) ? $this->name(CrayonUtil::ucwords($this->id)) : $this->name($name);
	}

	function __tostring() {
		return $this->name;
	}

	function id() {
		return $this->id;
	}

	function name($name = NULL) {
		if (!CrayonUtil::str($this->name, $name, FALSE)) {
			return $this->name;
		}
	}
}

/* Keeps track of usage */
class CrayonUsedResource extends CrayonResource {
	private $used = FALSE;

	function __construct($id, $name = NULL) {
		parent::__construct($id, $name);
	}

	function used($used = NULL) {
		if ($used === NULL) {
			return $this->used;
		} else {
			$this->used = ($used ? TRUE : FALSE);
		}
	}
}

/* Adds version */
class CrayonVersionResource extends CrayonResource {
	private $version = '';

	function __construct($id, $name = NULL, $version = NULL) {
		parent::__construct($id, $name);
		$this->version($version);
	}

	function version($version = NULL) {
		if ($version === NULL) {
			return $this->version;
		} else if (is_string($version)) {
			$this->version = $version;
		}
	}
}

?>