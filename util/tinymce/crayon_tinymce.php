<div id="crayon-tinymce-content">

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
//$add_overridden = CrayonGlobalSettings::val(CrayonSettings::TINYMCE_ADD_OVERRIDDEN);

class CrayonTinyMCEDialog {
	public static function select_resource($id, $resources, $current, $disabled = FALSE) {
		if (count($resources) > 0) {
			$disabled = $disabled ? ' disabled="true"' : '';
			$tag_id = 'crayon-tinymce-'.$id.'-input';
			echo '<select id="'.$tag_id.'" name="'.$tag_id.'" class="crayon-tinymce-input" data-id="'.$id.'" data-current="'.$current.'"'.$disabled.'>';
				foreach ($resources as $resource) {
					$asterisk = $current == $resource->id() ? ' *' : '';
					echo '<option value="'.$resource->id().'" '.selected($current, $resource->id()).' >'.$resource->name().$asterisk.'</option>';
				}
			echo '</select>';
		} else {
			// None found, default to text box
			echo '<input type="text" id="'.$tag_id.'" name="'.$tag_id.'" class="crayon-tinymce-input" data-id="'.$id.'" data-current="'.$current.'" />';
		}
	}
	
	public static function checkbox($id) {
		$tag_id = 'crayon-tinymce-'.$id.'-check';
		echo '<input type="checkbox" id="'.$tag_id.'" name="'.$tag_id.'" class="crayon-tinymce-check" data-id="'.$id.'" />';
	}
}

?>

	<table id="crayon-tinymce-table" class="describe">
		<tr>
			<th>Language</th>
			<td><?php CrayonTinyMCEDialog::select_resource('lang', $langs, $curr_lang); ?></td>
		</tr>
		<tr>
			<th>Code <input type="button" id="crayon-tinymce-clear" class="secondary-primary" value="Clear" name="clear" /></th>
			<td><textarea id="crayon-tinymce-code" name="code"></textarea></td>
		</tr>
		<tr>
			<th><?php CrayonTinyMCEDialog::checkbox(CrayonSettings::THEME) ?><span>Theme</span></th>
			<td><?php CrayonTinyMCEDialog::select_resource(CrayonSettings::THEME, $themes, $curr_theme, TRUE); ?></td>
		</tr>
		<tr>
			<th><?php CrayonTinyMCEDialog::checkbox(CrayonSettings::FONT) ?><span>Font</span></th>
			<td><?php CrayonTinyMCEDialog::select_resource(CrayonSettings::FONT, $fonts, $curr_font, TRUE); ?></td>
		</tr>
		<tr>
			<td colspan="2" style="text-align: center;">
				<input type="button" id="crayon-tinymce-submit" class="button-primary" value="Add Code" name="submit" />
			</td>
		</tr>
		<tr>
			<td colspan="2"><div id="crayon-tinymce-warning" class="updated crayon-tinymce-info"></div></td>
		</tr>
		<tr>
			<td colspan="2"><div id="crayon-tinymce-settings-info" class="crayon-tinymce-info">
			Choose which global settings to override from their current values (*) using the checkboxes.<br/>
			Changes to the global settings under <code>Crayon > Settings</code> will not affect these settings. 
			<?php
			/*
			if ($add_overridden) {
				echo 'Only settings overridden from their global values (*) will be added. Changes to global settings will not affect these overridden values.';
			} else {
				echo 'All defined settings will be added.';
			}
			*/
			?>
			</div></td>
		</tr>
	</table>
</div>
