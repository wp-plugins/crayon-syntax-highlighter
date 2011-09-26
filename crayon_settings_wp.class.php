<?php
require_once ('global.php');
require_once (CRAYON_LANGS_PHP);
require_once (CRAYON_THEMES_PHP);
require_once (CRAYON_FONTS_PHP);
require_once (CRAYON_SETTINGS_PHP);

/*  Manages global settings within WP and integrates them with CrayonSettings.
 CrayonHighlighter and any non-WP classes will only use CrayonSettings to separate
 the implementation of global settings and ensure any system can use them. */
class CrayonSettingsWP {
	// Properties and Constants ===============================================

	// A copy of the current options in db
	private static $options = NULL;
	private static $plugin_hook = '';
	
	const SETTINGS = 'crayon_fields';
	const FIELDS = 'crayon_settings';
	const OPTIONS = 'crayon_options';
	const GENERAL = 'crayon_general';
	const DEBUG = 'crayon_debug';
	const ABOUT = 'crayon_about';
	
	// Used on submit
	const LOG_CLEAR = 'log_clear';
	const LOG_EMAIL_ADMIN = 'log_email_admin';
	const LOG_EMAIL_DEV = 'log_email_dev';

	private function __construct() {}

	// Methods ================================================================

	public static function admin_load() {
		$page = add_options_page('Crayon Syntax Highlighter Settings', 'Crayon', 'manage_options', 'crayon_settings', 'CrayonSettingsWP::settings');
		self::$plugin_hook = $page;
		add_action("admin_print_scripts-$page", 'CrayonSettingsWP::admin_scripts');
		add_action("admin_print_styles-$page", 'CrayonSettingsWP::admin_styles');
		// Register settings, second argument is option name stored in db
		register_setting(self::FIELDS, self::OPTIONS, 'CrayonSettingsWP::settings_validate');
		add_action("admin_head-$page", 'CrayonSettingsWP::admin_init');
		add_filter('contextual_help', 'CrayonSettingsWP::cont_help', 10, 3);
	}

	public static function admin_styles() {
		global $CRAYON_VERSION;
		wp_enqueue_style('crayon_admin_style', plugins_url(CRAYON_STYLE_ADMIN, __FILE__), array(), $CRAYON_VERSION);
	}

	public static function admin_scripts() {
		global $CRAYON_VERSION;
		wp_enqueue_script('crayon_jquery', plugins_url(CRAYON_JQUERY, __FILE__), array(), $CRAYON_VERSION);
		wp_enqueue_script('crayon_admin_js', plugins_url(CRAYON_JS_ADMIN, __FILE__), array('crayon_jquery'), $CRAYON_VERSION);
		wp_enqueue_script('crayon_js', plugins_url(CRAYON_JS, __FILE__), array('crayon_jquery'), $CRAYON_VERSION);
	}

	public static function settings() {
		if (!current_user_can('manage_options')) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		?>

<div class="wrap">
<div id="icon-options-general" class="icon32"><br>
</div>
<h2>Crayon Syntax Highlighter Settings</h2>
<?php self::help(); ?>
<form action="options.php" method="post"><?php
		settings_fields(self::FIELDS);
		?>

		<?php
		do_settings_sections(self::SETTINGS);
		?>

<p class="submit"><input type="submit" name="submit" id="submit"
	class="button-primary" value="<?php
		_e('Save Changes');
		?>"> <input type="submit"
	name="<?php
		echo self::OPTIONS;
		?>[reset]" id="reset"
	class="button-primary" value="<?php
		_e('Reset Settings');
		?>"></p>
</form>
</div>

<?php
	}

	// Load the global settings and update them from the db
	public static function load_settings() {
		if (self::$options !== NULL) {
			return;
		}
		
		// Load settings from db
		if (!(self::$options = get_option(self::OPTIONS))) {
			self::$options = CrayonSettings::get_defaults_array();
			update_option(self::OPTIONS, self::$options);
		}
		
		// Initialise default global settings and update them from db
		CrayonGlobalSettings::set(self::$options);
				
		// Load all available languages and themes
		CrayonResources::langs()->load();
		CrayonResources::themes()->load();
		
		// For local file loading
		// This is used to decouple WP functions from internal Crayon classes
		CrayonGlobalSettings::site_http(home_url());
		CrayonGlobalSettings::site_path(ABSPATH);		
		CrayonGlobalSettings::plugin_path(plugins_url('', __FILE__));
		
		// Ensure all missing settings in db are replaced by default values
		$changed = FALSE;
		foreach (CrayonSettings::get_defaults_array() as $name => $value) {
			// Add missing settings
			if (!array_key_exists($name, self::$options)) {
				self::$options[$name] = $value;
				$changed = TRUE;
			}
		}
		// A setting was missing, update options
		if ($changed) {
			update_option(self::OPTIONS, self::$options);
		}
	}
	
	// Saves settings from CrayonGlobalSettings to the db
	public static function save_settings() {
		update_option(self::OPTIONS, CrayonGlobalSettings::get_array());
	}
	
	public static function wp_root_path() {
		return preg_replace('#wp\-content.*#', '', CRAYON_ROOT_PATH);
	}
	
	public static function wp_load_path() {
		return self::wp_root_path() . 'wp-load.php';
	}

	public static function admin_init() {		
		// Load default settings if they don't exist

		self::load_settings();
		// General

		self::add_section(self::GENERAL, 'General');
		self::add_field(self::GENERAL, 'Theme', 'themes');
		self::add_field(self::GENERAL, 'Font', 'fonts');
		self::add_field(self::GENERAL, 'Metrics', 'metrics');
		self::add_field(self::GENERAL, 'Toolbar', 'toolbar');
		self::add_field(self::GENERAL, 'Lines', 'lines');
		self::add_field(self::GENERAL, 'Code', 'code');
		self::add_field(self::GENERAL, 'Languages', 'langs');
		self::add_field(self::GENERAL, 'Files', 'files');
		self::add_field(self::GENERAL, 'Misc', 'misc');
		// Debug

		self::add_section(self::DEBUG, 'Debug');
		self::add_field(self::DEBUG, 'Errors', 'errors');
		self::add_field(self::DEBUG, 'Log', 'log');
		// ABOUT

		self::add_section(self::ABOUT, 'About');
		$image = '<div id="crayon-logo">

			<img src="' . plugins_url(CRAYON_LOGO, __FILE__) . '" /><br/></div>';
		self::add_field(self::ABOUT, $image, 'info');
	}

	// Wrapper functions

	private static function add_section($name, $title, $callback = NULL) {
		$callback = (empty($callback) ? 'blank' : $callback);
		add_settings_section($name, $title, 'CrayonSettingsWP::' . $callback, self::SETTINGS);
	}

	private static function add_field($section, $title, $callback, $args = array()) {
		$unique = preg_replace('#\\s#', '_', strtolower($title));
		add_settings_field($unique, $title, 'CrayonSettingsWP::' . $callback, self::SETTINGS, $section, $args);
	}

	// Validates all the settings passed from the form in $inputs

	public static function settings_validate($inputs) {
		global $CRAYON_EMAIL;
		// When reset button is pressed, remove settings so default loads next time
		if (array_key_exists('reset', $inputs)) {
			// Hide the help so we don't annoy them
			return array();
		}
		// Clear the log if needed
		if (array_key_exists(self::LOG_CLEAR, $_POST)) {
			CrayonLog::clear();
		}
		// Send to admin
		if (array_key_exists(self::LOG_EMAIL_ADMIN, $_POST)) {
			CrayonLog::email(get_bloginfo('admin_email'));
		}
		// Send to developer
		if (array_key_exists(self::LOG_EMAIL_DEV, $_POST)) {
			CrayonLog::email($CRAYON_EMAIL);
		}

		// Validate inputs
		foreach ($inputs as $input => $value) {
			// Convert all array setting values to ints
			$inputs[$input] = CrayonSettings::validate($input, $value);
		}
		
		// If settings don't exist in input, set them to default
		$global_settings = CrayonSettings::get_defaults();

		foreach ($global_settings as $setting) {
			// If boolean setting is not in input, then it is set to FALSE in the form
			if (!array_key_exists($setting->name(), $inputs)) {
				// For booleans, set to FALSE (unchecked boxes are not sent as POST)
				if (is_bool($setting->def())) {
					$inputs[$setting->name()] = FALSE;
				} else {
					/*  For array settings, set the input as the value, which by default is the
					 default index */
					if (is_array($setting->def())) {
						$inputs[$setting->name()] = $setting->value();
					} else {
						$inputs[$setting->name()] = $setting->def();
					}
				}
			}
		}
		return $inputs;
	}

	// Section callback functions

	public static function blank() {} // Used for required callbacks with blank content

	// Input Drawing ==========================================================

	// Used to read args and validate for input tags
	private static function input($args) {
		if (empty($args) || !is_array($args)) {
			return FALSE;
		}
		extract($args);
		$name = (!empty($name) ? $name : '');
		$size = (!empty($size) && is_numeric($size) ? $size : 40);
		$break = (!empty($break) && $break ? CRAYON_BR : '');
		$margin = (!empty($margin) && $margin ? '20px' : 0);
		// By default, inputs will trigger preview refresh
		$preview = (!empty($preview) && !$preview ? 0 : 1);
		//$options = get_option(self::OPTIONS);
		if (!array_key_exists($name, self::$options)) {
			return array();
		}
		return compact('name', 'size', 'break', 'margin', 'preview', 'options');
	}

	private static function textbox($args) {
		if (!($args = self::input($args))) {
			return;
		}
		$name = $size = $margin = $preview = $break = '';
		extract($args);
		echo '<input id="', $name, '" name="', self::OPTIONS, '[', $name, ']" size="', $size, '" type="text" value="',
			self::$options[$name], '" style="margin-left: ', $margin, '" crayon-preview="', ($preview ? 1 : 0), '" />', $break;
	}

	private static function checkbox($args, $line_break = TRUE, $preview = TRUE) {
		if (empty($args) || !is_array($args) || count($args) != 2) {
			return;
		}
		$name = $args[0];
		$text = $args[1];
		$checked = (!array_key_exists($name, self::$options)) ? '' : checked(TRUE, self::$options[$name], FALSE);
		echo '<input id="', $name, '" name="', self::OPTIONS, '[', $name, ']" type="checkbox" value="1"', $checked,
			' crayon-preview="', ($preview ? 1 : 0), '" /> ', '<span>', $text, '</span>', ($line_break ? CRAYON_BR : '');
	}

	// Draws a dropdown by loading the default value (an array) from a setting
	private static function dropdown($name, $line_break = TRUE, $preview = TRUE) {
		if (!array_key_exists($name, self::$options)) {
			return;
		}
		$opts = CrayonGlobalSettings::get($name)->def();
		if (is_array($opts)) {
			echo '<select id="', $name, '" name="', self::OPTIONS, '[', $name, ']" crayon-preview="', ($preview ? 1 : 0), '">';
			for ($i = 0; $i < count($opts); $i++) {
				echo '<option value="', $i,'" ', selected(self::$options[$name], $i), '>', $opts[$i], '</option>';
			}
			echo '</select>', ($line_break ? CRAYON_BR : '');
		}
	}

	// General Fields =========================================================
	public static function help() {
		global $CRAYON_WEBSITE;
		if (CrayonGlobalSettings::val(CrayonSettings::HIDE_HELP)) {
			return;
		}
		$url = plugins_url(CRAYON_AJAX_PHP, __FILE__) . '?' . CrayonSettings::HIDE_HELP . '=1';
		$web = $CRAYON_WEBSITE;
		echo <<<EOT
<div id="crayon-help" class="updated settings-error crayon-help">
	<span><strong>Howdy, coder!</strong> Thanks for using Crayon. Use <strong>help</strong> on the top-right to learn how to use the shortcode and basic features, or check out my <a href="#info">Twitter & Email</a>. For online help and info, visit <a target="_blank" href="{$web}">here</a>.</span>
	<a class="crayon-help-close" href="#" url="{$url}">X</a>
</div>
EOT;
	}
	
	public static function cont_help($contextual_help, $screen_id, $screen) {
		if ($screen_id == self::$plugin_hook) {
			if ( ($contextual_help = @file_get_contents(CRAYON_HELP_FILE)) !== FALSE) {
				$contextual_help = str_replace('{PLUGIN}', CrayonGlobalSettings::plugin_path(), $contextual_help);
			} else {
				$contextual_help = 'Help failed to load... Try <a href="#info">these</a> instead.';
			}
		}
		return $contextual_help;
	}

	public static function metrics() {
		self::checkbox(array(CrayonSettings::HEIGHT_SET, '<span class="crayon-span-50">Height: </span>'), FALSE);
		self::dropdown(CrayonSettings::HEIGHT_MODE, FALSE);
		echo ' ';
		self::textbox(array('name' => CrayonSettings::HEIGHT, 'size' => 8));
		echo ' ';
		self::dropdown(CrayonSettings::HEIGHT_UNIT);
		self::checkbox(array(CrayonSettings::WIDTH_SET, '<span class="crayon-span-50">Width: </span>'), FALSE);
		self::dropdown(CrayonSettings::WIDTH_MODE, FALSE);
		echo ' ';
		self::textbox(array('name' => CrayonSettings::WIDTH, 'size' => 8));
		echo ' ';
		self::dropdown(CrayonSettings::WIDTH_UNIT);
		$text = array('Top' => array(CrayonSettings::TOP_SET, CrayonSettings::TOP_MARGIN),
			'Bottom' => array(CrayonSettings::BOTTOM_SET, CrayonSettings::BOTTOM_MARGIN),
			'Left' => array(CrayonSettings::LEFT_SET, CrayonSettings::LEFT_MARGIN),
			'Right' => array(CrayonSettings::RIGHT_SET, CrayonSettings::RIGHT_MARGIN));
		foreach ($text as $p => $s) {
			$set = $s[0];
			$margin = $s[1];
			$preview = ($p == 'Left' || $p == 'Right');
			self::checkbox(array($set, '<span class="crayon-span-110">' . $p . ' Margin: </span>'), FALSE, $preview);
			echo ' ';
			self::textbox(array('name' => $margin, 'size' => 8, 'preview' => FALSE));
			echo '<span class="crayon-span-margin">Pixels</span>', CRAYON_BR;
		}
		echo '<span class="crayon-span" style="min-width: 135px;">Horizontal Alignment: </span>';
		self::dropdown(CrayonSettings::H_ALIGN);
		echo '<div id="crayon-float">';
		self::checkbox(array(CrayonSettings::FLOAT_ENABLE, 'Allow floating elements to surround Crayon'), FALSE, FALSE);
		echo '</div>';
	}

	public static function toolbar() {
		echo 'Display the Toolbar: ';
		self::dropdown(CrayonSettings::TOOLBAR);
		echo '<div id="' . CrayonSettings::TOOLBAR_OVERLAY . '">';
		self::checkbox(array(CrayonSettings::TOOLBAR_OVERLAY, 'Overlay the toolbar on code rather than push it down when possible'));
		self::checkbox(array(CrayonSettings::TOOLBAR_HIDE, 'Toggle the toolbar on single click when it is overlayed'));
		self::checkbox(array(CrayonSettings::TOOLBAR_DELAY, 'Delay hiding the toolbar on MouseOut'));
		echo '</div>';
		self::checkbox(array(CrayonSettings::SHOW_TITLE, 'Display the title when provided'));
		echo 'Display the language: ';
		self::dropdown(CrayonSettings::SHOW_LANG);
	}

	public static function lines() {
		self::checkbox(array(CrayonSettings::STRIPED, 'Display striped code lines'));
		self::checkbox(array(CrayonSettings::MARKING, 'Enable line marking for important lines'));
		self::checkbox(array(CrayonSettings::NUMS, 'Display line numbers by default'));
		self::checkbox(array(CrayonSettings::NUMS_TOGGLE, 'Enable line number toggling'));
	}

	public static function langs() {
		echo '<a name="langs"></a>';
		// Specialised dropdown for languages
		if (array_key_exists(CrayonSettings::FALLBACK_LANG, self::$options)) {
			if (($langs = CrayonParser::parse_all()) != FALSE) {
				$name = CrayonSettings::FALLBACK_LANG;
				echo 'When no language is provided, use the fallback: ', '<select id="', $name, '" name="', self::OPTIONS,
					'[', $name, ']" crayon-preview="1">';
				foreach ($langs as $lang) {

					$title = $lang->name() . ' [' . $lang->id() . ']';
					echo '<option value="', $lang->id(), '" ', selected(self::$options[CrayonSettings::FALLBACK_LANG],
						$lang->id()), '>', $title, '</option>';
				}
				// Information about parsing
				$parsed = CrayonResources::langs()->is_parsed();
				echo '</select>', CRAYON_BR, ($parsed ? '' : '<span class="crayon-error">'),
					CrayonUtil::spnum(count($langs), 'language has', 'languages have'), ' been detected. Parsing was ',
					($parsed ? 'successful' : 'unsuccessful'), '. ', ($parsed ? '' : '</span>');
				// Check if fallback from db is loaded
				$db_fallback = self::$options[CrayonSettings::FALLBACK_LANG]; // Fallback name from db

				if (!CrayonResources::langs()->is_loaded($db_fallback) || !CrayonResources::langs()->exists($db_fallback)) {
					echo '<br/><span class="crayon-error">The selected language with id "', $db_fallback,
						'" could not be loaded. </span>';
				}
				// Language parsing info
				echo '<a href="#" id="show-lang" onclick="show_langs(\'', plugins_url(CRAYON_LIST_LANGS_PHP, __FILE__),
					'\'); return false;">Show Languages</a>', '<div id="lang-info"></div>';
			} else {
				echo 'No languages could be parsed.';
			}
		}
	}

	public static function themes() {
		$db_theme = self::$options[CrayonSettings::THEME]; // Theme name from db
		if (!array_key_exists(CrayonSettings::THEME, self::$options)) {
			$db_theme = '';
		}
		$name = CrayonSettings::THEME;
		$themes = CrayonResources::themes()->get();
		echo '<select id="', $name, '" name="', self::OPTIONS, '[', $name, ']" crayon-preview="1">';
		foreach ($themes as $theme) {
			$title = $theme->name();
			echo '<option value="', $theme->id(), '" ', selected($db_theme, $theme->id()), '>', $title, '</option>';
		}
		echo '</select><span class="crayon-span-10"></span>';
		// Preview checkbox
		self::checkbox(array(CrayonSettings::PREVIEW, 'Enable Live Preview'), TRUE, FALSE);
		// Check if theme from db is loaded
		if (!CrayonResources::themes()->is_loaded($db_theme) || !CrayonResources::themes()->exists($db_theme)) {
			echo '<span class="crayon-error">The selected theme with id "', $db_theme, '" could not be loaded.</span>';
		}
		echo '<div id="crayon-preview" url="', plugins_url(CRAYON_PREVIEW_PHP, __FILE__), '"></div>';
	}

	public static function fonts() {
		$db_font = self::$options[CrayonSettings::FONT]; // Theme name from db
		if (!array_key_exists(CrayonSettings::FONT, self::$options)) {
			$db_font = '';
		}
		$name = CrayonSettings::FONT;
		$fonts = CrayonResources::fonts()->get();
		echo '<select id="', $name, '" name="', self::OPTIONS, '[', $name, ']" crayon-preview="1">';
		foreach ($fonts as $font) {
			$title = $font->name();
			echo '<option value="', $font->id(), '" ', selected($db_font, $font->id()), '>', $title, '</option>';
		}
		echo '</select><span class="crayon-span-10"></span>';
		self::checkbox(array(CrayonSettings::FONT_SIZE_ENABLE, 'Custom Font Size: '), FALSE);
		self::textbox(array('name' => CrayonSettings::FONT_SIZE, 'size' => 2));
		echo '<span class="crayon-span-margin">Pixels</span></br>';
		if ($db_font != CrayonFonts::DEFAULT_FONT && (!CrayonResources::fonts()->is_loaded($db_font) ||
			!CrayonResources::fonts()->exists($db_font))) {
			// Default font doesn't actually exist as a file, it means do not override default theme font
			echo '<span class="crayon-error">The selected font with id "', $db_font, '" could not be loaded.</span>';
		}
	}

	public static function code() {
		self::checkbox(array(CrayonSettings::PLAIN, 'Enable plain code view and display: '), FALSE);
		self::dropdown(CrayonSettings::SHOW_PLAIN);
		echo 'Display scrollbars (when needed): ';
		self::dropdown(CrayonSettings::SCROLL);
		echo 'Tab size in spaces: ';
		self::textbox(array('name' => CrayonSettings::TAB_SIZE, 'size' => 2, 'break' => TRUE));
		self::checkbox(array(CrayonSettings::TRIM_WHITESPACE, 'Remove whitespace surrounding the shortcode content'));
	}

	public static function files() {
		echo '<a name="files"></a>';
		echo 'When loading local files and a relative path is given for the URL, use the absolute path: ',
			'<div style="margin-left: 20px">', home_url(), '/';
		self::textbox(array('name' => CrayonSettings::LOCAL_PATH));
		echo '</div>Followed by your relative URL.';
	}

	public static function misc() {
		self::checkbox(array(CrayonSettings::TOUCHSCREEN, 'Disable mouse gestures for touchscreen devices (eg. MouseOver)'));
		self::checkbox(array(CrayonSettings::DISABLE_ANIM, 'Disable animations'));
		self::checkbox(array(CrayonSettings::DISABLE_RUNTIME, 'Disable runtime stats'));
		//self::checkbox(array(CrayonSettings::EXP_SCROLL, 'Use experimental CSS3 scrollbars (visible only in Chrome and Safari for now)'));
	}

	// Debug Fields ===========================================================

	public static function errors() {
		self::checkbox(array(CrayonSettings::ERROR_LOG, 'Log errors for individual Crayons'));
		self::checkbox(array(CrayonSettings::ERROR_LOG_SYS, 'Log system-wide errors'));
		//self::checkbox( array(CrayonSettings::ERROR_LOG_WARNING, 'Log system-wide warnings & notices') );

		self::checkbox(array(CrayonSettings::ERROR_MSG_SHOW, 'Display custom message for errors'));
		self::textbox(array('name' => CrayonSettings::ERROR_MSG, 'size' => 60, 'margin' => TRUE));
	}

	public static function log() {
		$log = CrayonLog::log();
		touch(CRAYON_LOG_FILE);
		$exists = file_exists(CRAYON_LOG_FILE);
		$writable = is_writable(CRAYON_LOG_FILE);
		if (!empty($log)) {
			echo '<div id="crayon-log-wrapper">', '<div id="crayon-log"><div id="crayon-log-text">', $log,
				'</div></div>', '<div id="crayon-log-controls">',
				'<input type="button" id="crayon-log-toggle" class="button-secondary" value="Show Log"> ',
				'<input type="submit" id="crayon-log-clear" name="', self::LOG_CLEAR ,
				'" class="button-secondary" value="Clear Log"> ', '<input type="submit" id="crayon-log-email" name="',
				self::LOG_EMAIL_ADMIN . '" class="button-secondary" value="Email Admin"> ',
				'<input type="submit" id="crayon-log-email" name="', self::LOG_EMAIL_DEV,
				'" class="button-secondary" value="Email Developer"> ', '</div>', '</div>';
		}
		echo '<span', (!empty($log)) ? ' class="crayon-span"' : '', '>', (empty($log)) ? 'The log is currently empty. ' : '',
			'The log file ', ($exists) ? 'exists' : 'doesn\'t exist', ' and is ', ($writable) ? 'writable' : 'not writable', '.</span>';
	}

	// About Fields ===========================================================

	public static function info() {
		global $CRAYON_VERSION, $CRAYON_DATE, $CRAYON_AUTHOR, $CRAYON_TWITTER, $CRAYON_EMAIL;
		echo '<a name="info"></a>';
		$version = '<b>Version:</b> ' . $CRAYON_VERSION . '<span class="crayon-span" style="width: 40px"></span>';
		$date = '<b>Build Date:</b> ' . $CRAYON_DATE;
		$developer = '<b>Developer:</b> ' . $CRAYON_AUTHOR;
		$links = '<a id="twitter-icon" href="' . $CRAYON_TWITTER . '" target="_blank"></a>
        			<a id="gmail-icon" href="mailto:' . $CRAYON_EMAIL . '" target="_blank"></a>';
		echo <<<EOT
<table id="crayon-info" border="0">
  <tr>
    <td>{$version}</td>
    <td>{$date}</td>
  </tr>
  <tr>
    <td>{$developer}</td>
    <td></td>
  </tr>
  <tr>
    <td colspan="2" style="text-align: centera;">{$links}</td>
  </tr>
</table>		

EOT;
	}
}
// Add the settings menus

if (defined('ABSPATH') && is_admin()) {
	add_action('admin_menu', 'CrayonSettingsWP::admin_load');
}

?>