<?php

require_once (dirname(dirname(__FILE__)) . '/crayon_wp.class.php');

$wp_root_path = str_replace('wp-content/plugins/' . CRAYON_DIR, '', CrayonUtil::pathf(CRAYON_ROOT_PATH));
require_once ($wp_root_path . 'wp-load.php');

echo '<link rel="stylesheet" href="', plugins_url(CRAYON_STYLE, dirname(__FILE__)),
	'?ver=', $CRAYON_VERSION, '" type="text/css" media="all" />';
echo '<script type="text/javascript">init();</script>';
echo '<div id="content">';
CrayonSettingsWP::load_settings(); // Run first to ensure global settings loaded

$crayon = CrayonWP::instance();

// Load settings from GET and validate
foreach ($_GET as $key => $value) {
	//echo $key, ' ', $value , '<br/>';
	$_GET[$key] = CrayonSettings::validate($key, $value);
}
$crayon->settings($_GET);
$settings = array(CrayonSettings::TOP_SET => TRUE, CrayonSettings::TOP_MARGIN => 10, 
		CrayonSettings::BOTTOM_SET => FALSE, CrayonSettings::BOTTOM_MARGIN => 0);
$crayon->settings($settings);

// Print the theme CSS
$theme_id = $crayon->setting_val(CrayonSettings::THEME);
$theme = CrayonResources::themes()->get($theme_id);
echo CrayonThemes::get_theme_as_css($theme);

// Load custom code based on language
$lang = $crayon->setting_val(CrayonSettings::FALLBACK_LANG);
$path = crayon_pf( dirname(__FILE__) . '/sample/' . $lang . '.txt', FALSE );

if ($lang && @file_exists($path)) {
	$crayon->url($path);
} else {
	$code = "
// A sample class
class Human {
	private int age = 0;
	public void birthday() {
		age++;
		print('Happy Birthday!');
	}
}
";
	$crayon->code($code);
}
$crayon->title('Sample Code');
$crayon->marked('5-7');
$crayon->output($highlight = true, $nums = true, $print = true);
echo '</div>';
CrayonWP::load_textdomain();
printf(crayon__('Change the %1$sfallback language%2$s to change the sample code. Lines 5-7 are marked.'), '<a href="#langs">', '</a>');

?>