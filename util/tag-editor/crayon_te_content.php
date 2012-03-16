<div id="crayon-te-content">

<?php 

$root = dirname(dirname(dirname(__FILE__)));
require_once ($root . '/crayon_wp.class.php');
require_once (CrayonWP::wp_load_path());
require_once ($root.'/crayon_settings_wp.class.php');
require_once (CRAYON_PARSER_PHP);

CrayonSettingsWP::load_settings();
$langs = CrayonParser::parse_all();
$curr_lang = CrayonGlobalSettings::val(CrayonSettings::FALLBACK_LANG);
$themes = CrayonResources::themes()->get();
$curr_theme = CrayonGlobalSettings::val(CrayonSettings::THEME);
$fonts = CrayonResources::fonts()->get();
$curr_font = CrayonGlobalSettings::val(CrayonSettings::FONT);

class CrayonTEContent {
	
	public static function select_resource($id, $resources, $current, $set_class = TRUE) {
		if (count($resources) > 0) {
			$class = $set_class ? 'class="crayon-setting-special"' : ''; 
			echo '<select id="crayon-'.$id.'" name="'.$id.'" '.$class.'>';
				foreach ($resources as $resource) {
					$asterisk = $current == $resource->id() ? ' *' : '';
					echo '<option value="'.$resource->id().'" '.selected($current, $resource->id()).' >'.$resource->name().$asterisk.'</option>';
				}
			echo '</select>';
		} else {
			// None found, default to text box
			echo '<input type="text" id="crayon-'.$id.'" name="'.$id.'" class="crayon-setting-special" />';
		}
	}
	
	public static function checkbox($id) {
		echo '<input type="checkbox" id="crayon-'.$id.'" name="'.$id.'" class="crayon-setting-special" />';
	}
	
	public static function textbox($id, $atts = array(), $set_class = TRUE) {
		$atts_str = '';
		$class = $set_class ? 'class="crayon-setting-special"' : '';
		foreach ($atts as $k=>$v) {
			$atts_str = $k.'="'.$v.'" ';
		}
		echo '<input type="text" id="crayon-'.$id.'" name="'.$id.'" '.$class.' '.$atts_str.' />';
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
		<!--<tr>
			<th><?php //CrayonTEContent::checkbox(CrayonSettings::THEME) ?><span>Theme</span></th>
			<td><?php //CrayonTEContent::select_resource(CrayonSettings::THEME, $themes, $curr_theme, TRUE); ?></td>
		</tr>
		--><!--<tr>
			<th><?php //CrayonTEContent::checkbox(CrayonSettings::FONT) ?><span>Font</span></th>
			<td><?php //CrayonTEContent::select_resource(CrayonSettings::FONT, $fonts, $curr_font, TRUE); ?></td>
		</tr>
		--><tr>
			<td colspan="2" style="text-align: center;">
				<input type="button" id="crayon-te-submit" class="button-primary" value="Add Code" name="submit" />
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
				Change the following settings to override their global values. <span class="crayon-setting-changed">Only changes (shown yellow) are applied.</span><br/>
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
		<!--<tr>
			<th>Toolbar</th>
			<td><?php //CrayonSettingsWP::toolbar(); ?></td>
		</tr>-->
	</table>
</div>
