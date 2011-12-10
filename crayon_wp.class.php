<?php
/*
Plugin Name: Crayon Syntax Highlighter
Plugin URI: http://ak.net84.net/
Description: Supports multiple languages, themes, highlighting from a URL, local file or post text. <a href="options-general.php?page=crayon_settings">View Settings.</a>
Version: 1.6.0
Author: Aram Kocharyan
Author URI: http://ak.net84.net/
Text Domain: crayon-syntax-highlighter
License: GPL2
	Copyright 2011	Aram Kocharyan	(email : akarmenia@gmail.com)
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
require_once ('global.php');
require_once (CRAYON_HIGHLIGHTER_PHP);
require_once ('crayon_settings_wp.class.php');

if (defined('ABSPATH')) {
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	set_crayon_info(get_plugin_data( __FILE__ ));
}

/* The plugin class that manages all other classes and integrates Crayon with WP */
class CrayonWP {
	// Properties and Constants ===============================================

	//	Associative array, keys are post IDs as strings and values are number of crayons parsed as ints
	private static $post_queue = array();
	// Whether we are displaying an excerpt
	private static $is_excerpt = FALSE;
	// Whether we have added styles and scripts
	private static $included = FALSE;
	// Used to keep Crayon IDs
	private static $next_id = 0;
	
	// Used to detect the shortcode
	const REGEX_CLOSED = '(?:\[[\t ]*crayon(?:-(\w+))?\b([^\]]*)/[\t ]*\])'; // [crayon atts="" /]
	const REGEX_TAG =    '(?:\[[\t ]*crayon(?:-(\w+))?\b([^\]]*)\]\r?\n?(.*?)\r?\n?\[[\t ]*/[\t ]*crayon\b[^\]]*\])'; // [crayon atts="" /] ... [/crayon]
	
	const REGEX_CLOSED_NO_CAPTURE = '(?:\[[\t ]*crayon\b[^\]]*/[\t ]*\])';
	const REGEX_TAG_NO_CAPTURE =    '(?:\[[\t ]*crayon\b[^\]]*\]\r?\n?.*?\r?\n?\[[\t ]*/[\t ]*crayon\b[^\]]*\])';

	// Methods ================================================================

	private function __construct() {}
	
	public static function regex() {
		return '#(?<!\$)(?:'. self::REGEX_CLOSED .'|'. self::REGEX_TAG .')(?!\$)#s';
	}
	
	public static function regex_with_id($id) {
		return '#(?<!\$)(?:(?:\[[\t ]*crayon-'.$id.'\b[^\]]*/[\t ]*\])|(?:\[[\t ]*crayon-'.$id.'\b[^\]]*\]\r?\n?.*?\r?\n?\[[\t ]*/[\t ]*crayon\b[^\]]*\]))(?!\$)#s';
	}
	
	public static function regex_no_capture() {
		return '#(?<!\$)(?:'. self::REGEX_CLOSED_NO_CAPTURE .'|'. self::REGEX_TAG_NO_CAPTURE .')(?!\$)#s';
	}
	
	public static function regex_ignore() {
		return '#(?:\$('. self::REGEX_CLOSED_NO_CAPTURE .')\$?)|'. '(?:\$(\[[\t ]*crayon\b))|(?:(\[[\t ]*/[\t ]*crayon\b[^\]]*\])\$)' .'#s';
	}
	
	/**
	 * Adds the actual Crayon instance, should only be called by add_shortcode()
	 */
	private static function shortcode($atts, $content = NULL, $id = NULL) {
		// Lowercase attributes
		$lower_atts = array();
		foreach ($atts as $att=>$value) {
			$lower_atts[trim(strip_tags(strtolower($att)))] = $value;
		}
		$atts = $lower_atts;
		
		// Load attributes from shortcode
		$allowed_atts = array('url' => NULL, 'lang' => NULL, 'title' => NULL, 'mark' => NULL);
		$filtered_atts = shortcode_atts($allowed_atts, $atts);
		
		// Clean attributes
		$keys = array_keys($filtered_atts);
		for ($i = 0; $i < count($keys); $i++) {
			$key = $keys[$i];
			$filtered_atts[$key] = trim(strip_tags($filtered_atts[$key]));
		}
		
		// Contains all other attributes not found in allowed, used to override global settings
		$extra_attr = array();
		if (!empty($atts)) {
			$extra_attr = array_diff_key($atts, $allowed_atts);
			$extra_attr = CrayonSettings::smart_settings($extra_attr);
		}
		$lang = $title = $mark = '';
		extract($filtered_atts);
		
		$crayon = self::instance($extra_attr, $id);
		
		// Set URL
		$url = isset($url) ? $url : '';
		if (!empty($url)) {
			$crayon->url($url);
		}
		if (!empty($content)) {
			$crayon->code($content);
		}
		// Set attributes, should be set after URL to allow language auto detection
		$crayon->language($lang);
		$crayon->title($title);
		$crayon->marked($mark);
		
		// Determine if we should highlight
		$highlight = array_key_exists('highlight', $atts) ? CrayonUtil::str_to_bool($atts['highlight'], FALSE) : TRUE;
		
		return $crayon->output($highlight, $nums = true, $print = false);
	}

	/* Returns Crayon instance */
	public static function instance($extra_attr = array(), $id = NULL) {
		// Create Crayon
		$crayon = new CrayonHighlighter();
		
		/* Load settings and merge shortcode attributes which will override any existing.
		 * Stores the other shortcode attributes as settings in the crayon. */
		if (!empty($extra_attr)) {
			$crayon->settings($extra_attr);
		}
		if (!empty($id)) {
			$crayon->id($id);
		}
		return $crayon;
	}

	/* Search for Crayons in posts and queue them for creation */
	public static function the_posts($posts) {
		if (empty($posts)) {
			return $posts;
		}
		
		global $wp_query;
		
		if (empty($wp_query->posts)) {
			return;
		}
		
		// Whether to enqueue syles/scripts
		$enqueue = FALSE;
		
		// Search for shortcode in query
		foreach ($wp_query->posts as $post) {
			
			// Add IDs to the Crayons
			$post->post_content = preg_replace_callback('#(?<!\$)\[[\t ]*crayon#i', 'CrayonWP::add_crayon_id', $post->post_content);
			
			// Only include if a post exists with Crayon tag
			preg_match_all(self::regex(), $post->post_content, $matches);
			
			if ( count($matches[0]) != 0 ) {
				// Crayons found!
				CrayonSettingsWP::load_settings(); // Run first to ensure global settings loaded
				
				$full_matches = $matches[0];
				$closed_ids = $matches[1];
				$closed_atts = $matches[2];
				$open_ids = $matches[3];
				$open_atts = $matches[4];
				$contents = $matches[5];
				
				// Make sure we enqueue the styles/scripts
				$enqueue = TRUE;
				
				// Mark the default theme as being used
				if ( ($default_theme_id = CrayonGlobalSettings::val(CrayonSettings::THEME)) != NULL ) {
					CrayonResources::themes()->set_used($default_theme_id);
				}
				
				for ($i = 0; $i < count($full_matches); $i++) {
					// Get attributes
					if ( !empty($closed_atts[$i]) ) {
						$atts = $closed_atts[$i];
					} else if ( !empty($open_atts[$i]) ) {
						$atts = $open_atts[$i];
					} else {
						$atts = '';
					}
					
					// Capture attributes
					preg_match_all('#([^="\'\s]+)[\t ]*=[\t ]*("|\')([^"]+?)\2#', $atts, $att_matches);
					$atts_array = array();
					if ( count($att_matches[0]) != 0 ) {
						for ($j = 0; $j < count($att_matches[1]); $j++) {
							$atts_array[trim($att_matches[1][$j])] = trim($att_matches[3][$j]);
						}
					}
					
					// Detect if a theme is used
					if (array_key_exists('theme', $atts_array)) {
						$theme_id = $atts_array['theme'];
						CrayonResources::themes()->set_used($theme_id);
					}
					
					// Add array of atts and content to post queue with key as post ID
					$id = !empty($open_ids[$i]) ? $open_ids[$i] : $closed_ids[$i];
					self::$post_queue[strval($post->ID)][$id] = array('post_id'=>$post->ID, 'atts'=>$atts_array, 'code'=>$contents[$i]);
				}
			}
		}
		
		if (!is_admin() && $enqueue && !self::$included) {
			self::enqueue_resources();
		}
		
		return $posts;
	}
	
	private static function add_crayon_id($content) {
		return $content[0].'-'.uniqid();
	}
	
	private static function get_crayon_id() {
		return self::$next_id++;
	}
	
	private static function enqueue_resources() {
		global $CRAYON_VERSION;
		wp_enqueue_style('crayon-style', plugins_url(CRAYON_STYLE, __FILE__), array(), $CRAYON_VERSION);
		
		$css = CrayonResources::themes()->get_used_theme_css();
		foreach ($css as $theme=>$url) {
			wp_enqueue_style('crayon-theme-'.$theme, $url, array(), $CRAYON_VERSION);
		}
		
		wp_enqueue_script('crayon-jquery', plugins_url(CRAYON_JQUERY, __FILE__), array(), $CRAYON_VERSION);
		wp_enqueue_script('crayon-js', plugins_url(CRAYON_JS, __FILE__), array('crayon-jquery'), $CRAYON_VERSION);
		wp_enqueue_script('crayon-jquery-popup', plugins_url(CRAYON_JQUERY_POPUP, __FILE__), array('crayon-jquery'), $CRAYON_VERSION);
		self::$included = TRUE;
	}
	
	// Add Crayon into the_content
	public static function the_content($the_content) {
		global $post;
		// Go through queued posts and find crayons		
		$post_id = strval($post->ID);
		
		if (self::$is_excerpt) {
			// Remove Crayon from content if we are displaying an excerpt
			return preg_replace(self::regex_no_capture(), '', $the_content);
		}
		
		// Find if this post has Crayons
		if ( array_key_exists($post_id, self::$post_queue) ) {
			// XXX We want the plain post content, no formatting
			$the_content_original = $the_content;
			// Loop through Crayons
			$post_in_queue = self::$post_queue[$post_id];
			foreach ($post_in_queue as $id=>$v) {
				$atts = $v['atts'];
				$content = $v['code']; // The formatted crayon we replace post content with
				// Remove '$' from $[crayon]...[/crayon]$ contained within [crayon] tag content
				$content = self::crayon_remove_ignore($content);
				// Apply shortcode to the content
				$crayon = self::shortcode($atts, $content, $id);
				$the_content = CrayonUtil::preg_replace_escape_back(self::regex_with_id($id), $crayon, $the_content, 1, $count);
			}
		}
		// Remove '$' from $[crayon]...[/crayon]$ in post body
		// XXX Do this after applying shortcode to avoid matching
		$the_content = self::crayon_remove_ignore($the_content);
		return $the_content;
	}
	
	// Remove Crayons from the_excerpt
	public static function the_excerpt($the_excerpt) {
		self::$is_excerpt = TRUE;
		$the_excerpt = wpautop(wp_trim_excerpt(''));
		self::$is_excerpt = FALSE;
		return $the_excerpt;
	}
	
	// Check if the $[crayon]...[/crayon]$ notation has been used to ignore [crayon] tags within posts
	public static function crayon_remove_ignore($the_content) {
		$the_content = preg_replace('#\$('. self::REGEX_CLOSED_NO_CAPTURE .')\$?#', '$1', $the_content);
		$the_content = preg_replace('#\$(\[[\t ]*crayon\b)#', '$1', $the_content);
		$the_content = preg_replace('#(\[[\t ]*/[\t ]*crayon\b[^\]]*\])\$#', '$1', $the_content);
		return $the_content;
	}

	public static function init() {
		self::load_textdomain();
	}
	
	public static function load_textdomain() {
		load_plugin_textdomain('crayon-syntax-highlighter', false, CRAYON_DIR.CRAYON_TRANS_DIR);
	}
	
	public static function install() {
		
	}

	public static function uninstall() {
		
	}
	
	public static function crayon_theme_css() {
		global $CRAYON_VERSION;
		$css = CrayonResources::themes()->get_used_theme_css();
		foreach ($css as $theme=>$url) {
			wp_enqueue_style('crayon-theme-'.$theme, $url, array(), $CRAYON_VERSION);
		}
	}
}

// Only if WP is loaded
if (defined('ABSPATH')) {
	register_activation_hook(__FILE__, 'CrayonWP::install');
	register_deactivation_hook(__FILE__, 'CrayonWP::uninstall');
	
	// Filters and Actions
	add_filter('the_posts', 'CrayonWP::the_posts');
	add_filter('the_content', 'CrayonWP::the_content');
	add_filter('the_excerpt', 'CrayonWP::the_excerpt');
	add_filter('init', 'CrayonWP::init');
}
?>