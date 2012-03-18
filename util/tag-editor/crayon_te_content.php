<div id="crayon-te-content">

<?php 

$root = dirname(dirname(dirname(__FILE__)));
require_once ($root . '/crayon_wp.class.php');
require_once (CrayonWP::wp_load_path());
require_once ($root.'/crayon_settings_wp.class.php');
require_once ('crayon_tag_editor_wp.class.php');
require_once (CRAYON_PARSER_PHP);

CrayonSettingsWP::load_settings();
$langs = CrayonParser::parse_all();
$curr_lang = CrayonGlobalSettings::val(CrayonSettings::FALLBACK_LANG);
$themes = CrayonResources::themes()->get();
$curr_theme = CrayonGlobalSettings::val(CrayonSettings::THEME);
$fonts = CrayonResources::fonts()->get();
$curr_font = CrayonGlobalSettings::val(CrayonSettings::FONT);
CrayonTagEditorWP::init_settings();

class CrayonTEContent {
	
	public static function select_resource($id, $resources, $current, $set_class = TRUE) {
		$id = CrayonSettings::PREFIX . $id;
		if (count($resources) > 0) {
			$class = $set_class ? 'class="'.CrayonSettings::SETTING.' '.CrayonSettings::SETTING_SPECIAL.'"' : ''; 
			echo '<select id="'.$id.'" name="'.$id.'" '.$class.' '.CrayonSettings::SETTING_ORIG_VALUE.'="'.$current.'">';
				foreach ($resources as $resource) {
					$asterisk = $current == $resource->id() ? ' *' : '';
					echo '<option value="'.$resource->id().'" '.selected($current, $resource->id()).' >'.$resource->name().$asterisk.'</option>';
				}
			echo '</select>';
		} else {
			// None found, default to text box
			echo '<input type="text" id="'.$id.'" name="'.$id.'" class="'.CrayonSettings::SETTING.' '.CrayonSettings::SETTING_SPECIAL.'" />';
		}
	}
	
	public static function checkbox($id) {
		$id = CrayonSettings::PREFIX . $id;
		echo '<input type="checkbox" id="'.$id.'" name="'.$id.'" class="'.CrayonSettings::SETTING.' '.CrayonSettings::SETTING_SPECIAL.'" />';
	}
	
	public static function textbox($id, $atts = array(), $set_class = TRUE) {
		$id = CrayonSettings::PREFIX . $id;
		$atts_str = '';
		$class = $set_class ? 'class="'.CrayonSettings::SETTING.' '.CrayonSettings::SETTING_SPECIAL.'"' : '';
		foreach ($atts as $k=>$v) {
			$atts_str = $k.'="'.$v.'" ';
		}
		echo '<input type="text" id="'.$id.'" name="'.$id.'" '.$class.' '.$atts_str.' />';
	}
	
	public static function submit($i) {
		?>
		<input type="button" id="<?php echo '#' . CrayonTagEditorWP::$settings['submit_css'] . '-' . $i; ?>" class="button-primary <?php echo CrayonTagEditorWP::$settings['submit_css']; ?>" value="<?php echo CrayonTagEditorWP::$settings['submit_add']; ?>" name="submit" />
		<?php
	}
}

?>

	<table id="crayon-te-table" class="describe">
		<tr class="crayon-tr-center">
			<th>Title</th>
			<td><?php CrayonTEContent::textbox('title', array('placeholder'=>'A short description')); ?></td>
		</tr>
		<tr class="crayon-tr-center">
			<th>Language</th>
			<td>
				<?php CrayonTEContent::select_resource('lang', $langs, $curr_lang); ?>
				<span class="crayon-te-section">Marked Lines</span>
				<?php CrayonTEContent::textbox('mark', array('placeholder'=>'(e.g. 1,2,3-5)')); ?>
			</td>
		</tr>
		<tr class="crayon-tr-center">
			<th>Code <input type="button" id="crayon-te-clear" class="secondary-primary" value="Clear" name="clear" /></th>
			<td><textarea id="crayon-te-code" name="code" placeholder="Copy your code here, or type it in manually."></textarea></td>
		</tr>
		<tr>
			<td colspan="2" style="text-align: center;">
				<?php CrayonTEContent::submit(1); ?>
			</td>
		</tr>
<!--		<tr>-->
<!--			<td colspan="2"><div id="crayon-te-warning" class="updated crayon-te-info"></div></td>-->
<!--		</tr>-->
		<tr>
			<td colspan="2">
			<hr />
			<div><h2 class="crayon-te-heading">Settings</h2></div>
			<div id="crayon-te-settings-info" class="crayon-te-info">
				Change the following settings to override their global values. <span class="<?php echo CrayonSettings::SETTING_CHANGED ?>">Only changes (shown yellow) are applied.</span><br/>
				Future changes to the global settings under <code>Crayon > Settings</code> won't affect overridden settings. 
			</div></td>
		</tr>
		<?php
			$sections = array('Theme', 'Font', 'Metrics', 'Toolbar', 'Lines', 'Code');
			foreach ($sections as $section) {
				echo '<tr><th>', crayon__($section), '</th><td>';
				call_user_func('CrayonSettingsWP::'.strtolower($section), TRUE);
				echo '</td></tr>';
			}
		?>
		<tr>
			<td colspan="2" style="text-align: center;">
				<hr />
				<?php CrayonTEContent::submit(2); ?>
			</td>
		</tr>
	</table>
</div>
