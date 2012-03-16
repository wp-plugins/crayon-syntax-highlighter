<?php
/*
Plugin Name: Crayon Syntax Highlighter
Plugin URI: http://ak.net84.net/projects/crayon-syntax-highlighter
Description: Supports multiple languages, themes, highlighting from a URL, local file or post text.
Version: 1.8.3
Author: Aram Kocharyan
Author URI: http://ak.net84.net/
Text Domain: crayon-syntax-highlighter
Domain Path: /trans/
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
require_once ('util/tag-editor/crayon_tag_editor_wp.class.php');
require_once ('crayon_settings_wp.class.php');

if (defined('ABSPATH')) {
	// Used to get plugin version info
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	crayon_set_info(get_plugin_data( __FILE__ ));
}

/* The plugin class that manages all other classes and integrates Crayon with WP */
class CrayonWP {
	// Properties and Constants ===============================================

	//	Associative array, keys are post IDs as strings and values are number of crayons parsed as ints
	private static $post_queue = array();
	// Ditto for comments
	private static $comment_queue = array();
	private static $post_captures = array();
	private static $comment_captures = array();
	// Whether we are displaying an excerpt
	private static $is_excerpt = FALSE;
	// Whether we have added styles and scripts
	private static $enqueued = FALSE;
	// Whether we have already printed the wp head 
	private static $wp_head = FALSE;
	// Used to keep Crayon IDs
	private static $next_id = 0;
	// Array of tag search strings
	private static $search_tags = array('[crayon', '<pre', '[plain');
	// String to store the regex for capturing mini tags
	private static $alias_regex = '';
	private static $is_special_tag_init = FALSE;  
	
	// Used to detect the shortcode
	const REGEX_CLOSED = '(?:\[\s*crayon(?:-(\w+))?\b([^\]]*)/\s*\])'; // [crayon atts="" /]
	const REGEX_TAG =    '(?:\[\s*crayon(?:-(\w+))?\b([^\]]*)\][\r\n]*?(.*?)[\r\n]*?\[\s*/\s*crayon\s*\])'; // [crayon atts=""] ... [/crayon]
	
	const REGEX_CLOSED_NO_CAPTURE = '(?:\[\s*crayon\b[^\]]*/\])';
	const REGEX_TAG_NO_CAPTURE =    '(?:\[\s*crayon\b[^\]]*\][\r\n]*?.*?[\r\n]*?\[/crayon\])';
	
	const REGEX_QUICK_CAPTURE = '(?:\[\s*crayon[^\]]*\].*?\[\s*/\s*crayon\s*\])|(?:\[\s*crayon[^\]]*/\s*\])';
	
	const REGEX_BETWEEN_PARAGRAPH = '<p[^<]*>(?:[^<]*<(?!/?p(\s+[^>]*)?>)[^>]+(\s+[^>]*)?>)*[^<]*((?:\[\s*crayon[^\]]*\].*?\[\s*/\s*crayon\s*\])|(?:\[\s*crayon[^\]]*/\s*\]))(?:[^<]*<(?!/?p(\s+[^>]*)?>)[^>]+(\s+[^>]*)?>)*[^<]*</p[^<]*>';
	const REGEX_BETWEEN_PARAGRAPH_SIMPLE = '(<p(?:\s+[^>]*)?>)(.*?)(</p(?:\s+[^>]*)?>)';
	const REGEX_BR_BEFORE = '<br\s*/?>\s*(\[\s*crayon)';
	const REGEX_BR_AFTER = '(\[\s*/\s*crayon\s*\])\s*<br\s*/?>';
	
	const REGEX_ID = '#(?<!\$)\[\s*crayon#i';
	
	const MODE_NORMAL = 0, MODE_JUST_CODE = 1, MODE_PLAIN_CODE = 2;

	// Methods ================================================================

	private function __construct() {}
	
	public static function regex() {
		return '#(?<!\$)(?:'. self::REGEX_CLOSED .'|'. self::REGEX_TAG .')(?!\$)#s';
	}
	
	public static function regex_with_id($id) {
		return '#(?<!\$)(?:(?:\[\s*crayon-'.$id.'\b[^\]]*/\s*\])|(?:\[\s*crayon-'.$id.'\b[^\]]*\][\r\n]*?.*?[\r\n]*?\[\s*/\s*crayon\s*\]))(?!\$)#s';
	}
	
	public static function regex_no_capture() {
		return '#(?<!\$)(?:'. self::REGEX_CLOSED_NO_CAPTURE .'|'. self::REGEX_TAG_NO_CAPTURE .')(?!\$)#s';
	}
	
	/**
	 * Adds the actual Crayon instance, should only be called by add_shortcode()
	 * $mode can be: 0 = return crayon content, 1 = return only code, 2 = return only plain code 
	 */
	private static function shortcode($atts, $content = NULL, $id = NULL) {
		CrayonLog::debug('shortcode');
		
		// Load attributes from shortcode
		$allowed_atts = array('url' => NULL, 'lang' => NULL, 'title' => NULL, 'mark' => NULL, 'inline' => NULL);
		$filtered_atts = shortcode_atts($allowed_atts, $atts);
		$filtered_atts['lang'] = strtolower($filtered_atts['lang']);
		
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
		$lang = $title = $mark = $inline = '';
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
		$crayon->is_inline($inline);
		
		// Determine if we should highlight
		$highlight = array_key_exists('highlight', $atts) ? CrayonUtil::str_to_bool($atts['highlight'], FALSE) : TRUE;
		$crayon->is_highlighted($highlight);
		return $crayon;
	}

	/* Returns Crayon instance */
	public static function instance($extra_attr = array(), $id = NULL) {
		CrayonLog::debug('instance');
		
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
	
	/* Uses the main query */
	public static function wp() {
		global $wp_the_query;
		$posts = $wp_the_query->posts;
		self::the_posts($posts);
	}
	
	public static function capture_crayons($wp_id, $wp_content) {
		// Will contain captured crayons and altered $wp_content
		$capture = array('capture' => array(), 'content' => $wp_content, 'has_captured' => FALSE);
		
		// If we get query for a page, then that page might have a template and load more posts containing Crayons
		// By this state, we would be unable to enqueue anything (header already written).
		if (CrayonGlobalSettings::val(CrayonSettings::SAFE_ENQUEUE) && is_page($wp_id)) {
			CrayonGlobalSettings::set(CrayonSettings::ENQUEUE_THEMES, false);
			CrayonGlobalSettings::set(CrayonSettings::ENQUEUE_FONTS, false);
		}
		
		// To improve efficiency, avoid complicated regex with a simple check first
		if (CrayonUtil::strposa($wp_content, self::$search_tags, TRUE) === FALSE) {
			return $capture;
		}
		
		// Convert <pre> tags to crayon tags, if needed
		if (CrayonGlobalSettings::val(CrayonSettings::CAPTURE_PRE)) {
			// XXX This will fail if <pre></pre> is used inside another <pre></pre>
			$wp_content = preg_replace_callback('#(?<!\$)<\s*pre(?=(?:([^>]*)\bclass\s*=\s*(["\'])(.*?)\2([^>]*))?)([^>]*)>(.*?)<\s*/\s*pre\s*>#msi', 'CrayonWP::pre_tag', $wp_content);
			// XXX For encoded <pre></pre> tags
			$wp_content = preg_replace_callback('#(?<!\$)&lt;\s*pre(?=(?:(.*?)\bclass\s*=\s*(["\'])(.*?)\2(.*?))?)(.*?)&gt;(.*?)&lt;\s*/\s*pre\s*&gt;#msi', 'CrayonWP::pre_tag', $wp_content);
			// XXX old versions
			//$wp_content = preg_replace('#(?<!\$)<pre([^\>]*)>(.*?)</pre>(?!\$)#msi', '[crayon\1]\2[/crayon]', $wp_content);
			//$wp_content = preg_replace('#(?<!\$)&lt;\s*pre(.*?)&gt;(.*?)&lt;\s*/\s*pre\s*&gt;(?!\$)#msi', '[crayon\1]\2[/crayon]', $wp_content);
		}
		
		// Convert mini [php][/php] tags to crayon tags, if needed
		if (CrayonGlobalSettings::val(CrayonSettings::CAPTURE_MINI_TAG)) {
			$wp_content = preg_replace('#(?<!\$)\[('.self::$alias_regex.')\b([^\]]*)\](.*?)\[\s*/\s*(?:\1)\s*\](?!\$)#msi', '[crayon lang="\1" \2]\3[/crayon]', $wp_content);
			$wp_content = preg_replace('#(?<!\$)\[('.self::$alias_regex.')\b([^\]]*)/\s*\](?!\$)#msi', '[crayon lang="\1" \2 /]', $wp_content);
		}
		
		// Convert inline {php}{/php} tags to crayon tags, if needed
		if (CrayonGlobalSettings::val(CrayonSettings::INLINE_TAG)) {
			$wp_content = preg_replace('#(?<!\$)\{('.self::$alias_regex.')\b([^\}]*)\}(.*?)\{/(?:\1)\}(?!\$)#msi', '[crayon lang="\1" inline="true" \2]\3[/crayon]', $wp_content);
		}
		
		// Convert [plain] tags into <pre><code></code></pre>, if needed
		if (CrayonGlobalSettings::val(CrayonSettings::PLAIN_TAG)) {
			$wp_content = preg_replace_callback('#(?<!\$)\[plain\](.*?)\[/plain\]#msi', 'CrayonFormatter::plain_code', $wp_content);
		}
		
		// Convert `` backquote tags into <code></code>, if needed
		if (CrayonGlobalSettings::val(CrayonSettings::BACKQUOTE)) {
			$wp_content = preg_replace('#(?<!\\\\)`(.*?)`#msi', '<code>\1</code>', $wp_content);
		}
		
		// Add IDs to the Crayons
		$wp_content = preg_replace_callback(self::REGEX_ID, 'CrayonWP::add_crayon_id', $wp_content);
		
		// Only include if a post exists with Crayon tag
		preg_match_all(self::regex(), $wp_content, $matches);
		
		// We need to escape ignored Crayons, since they won't be captured
		$capture['content'] = self::crayon_remove_ignore($wp_content);
		
		if ( count($matches[0]) != 0 ) {
			// Crayons found! Load settings first to ensure global settings loaded
			CrayonSettingsWP::load_settings();
			$capture['has_captured'] = TRUE;
			
			$full_matches = $matches[0];
			$closed_ids = $matches[1];
			$closed_atts = $matches[2];
			$open_ids = $matches[3];
			$open_atts = $matches[4];
			$contents = $matches[5];
			
			// Make sure we enqueue the styles/scripts
			$enqueue = TRUE;
			
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
				preg_match_all('#([^="\'\s]+)[\t ]*=[\t ]*("|\')(.*?)\2#', $atts, $att_matches);
				
				$atts_array = array();
				if ( count($att_matches[0]) != 0 ) {
					for ($j = 0; $j < count($att_matches[1]); $j++) {
						$atts_array[trim(strtolower($att_matches[1][$j]))] = trim($att_matches[3][$j]);
					}
				}
				
				// Capture theme
				$theme_id = array_key_exists(CrayonSettings::THEME, $atts_array) ? $atts_array[CrayonSettings::THEME] : '';
				$theme = CrayonResources::themes()->get($theme_id);
				// If theme not found, use fallbacks
				if (!$theme) {
					// Given theme is invalid, try global setting
					$theme_id = CrayonGlobalSettings::val(CrayonSettings::THEME);
					$theme = CrayonResources::themes()->get($theme_id);
					if (!$theme) {
						// Global setting is invalid, fall back to default
						$theme = CrayonResources::themes()->get_default();
						$theme_id = CrayonThemes::DEFAULT_THEME;
					}
				}
				// If theme is now valid, change the array
				if ($theme) {
					$atts_array[CrayonSettings::THEME] = $theme_id;
					$theme->used(TRUE);
				}
				
				// Capture font
				$font_id = array_key_exists(CrayonSettings::FONT, $atts_array) ? $atts_array[CrayonSettings::FONT] : '';
				$font = CrayonResources::fonts()->get($font_id);
				// If font not found, use fallbacks
				if (!$font) {
					// Given font is invalid, try global setting
					$font_id = CrayonGlobalSettings::val(CrayonSettings::FONT);
					$font = CrayonResources::fonts()->get($font_id);
					if (!$font) {
						// Global setting is invalid, fall back to default
						$font = CrayonResources::fonts()->get_default();
						$font_id = CrayonFonts::DEFAULT_FONT;
					}
				}
				// If font is now valid, change the array
				if ($font/* != NULL && $font_id != CrayonFonts::DEFAULT_FONT*/) {
					$atts_array[CrayonSettings::FONT] = $font_id;
					$font->used(TRUE);
				}
				
				// Add array of atts and content to post queue with key as post ID
				$id = !empty($open_ids[$i]) ? $open_ids[$i] : $closed_ids[$i];
				$code = self::crayon_remove_ignore($contents[$i]);
				$capture['capture'][$id] = array('post_id'=>$wp_id, 'atts'=>$atts_array, 'code'=>$code);
			}
			
		}
		
		return $capture;
	}
	
	/* Search for Crayons in posts and queue them for creation */
	public static function the_posts($posts) {
		CrayonLog::debug('the_posts');
		
		// Whether to enqueue syles/scripts
		$enqueue = FALSE;
		CrayonSettingsWP::load_settings(TRUE); // Load just the settings from db, for now
		
		self::init_special_tags();
		
		// Search for shortcode in posts
		foreach ($posts as $post) {
			$id_str = strval($post->ID);
			if ( isset(self::$post_queue[$id_str]) ) {
				// Don't capture twice
				continue;
			}
			// Capture post Crayons
			$captures = self::capture_crayons($post->ID, $post->post_content);
			if ($captures['has_captured'] === TRUE) {
				self::$post_queue[$id_str] = array();
				foreach ($captures['capture'] as $capture_id=>$capture_content) {
					self::$post_queue[$id_str][$capture_id] = $capture_content;
				}
				// TODO improve by using capture, but careful not to undo changes by other plugins
				$post->post_content = $captures['content'];
				self::$post_captures[$id_str] = $captures['content']; 
			}
			
			// Search for shortcode in comments
			if (CrayonGlobalSettings::val(CrayonSettings::COMMENTS)) {
				$comments = get_comments(array('post_id' => $post->ID));
			    foreach ($comments as $comment) {
				    $id_str = strval($comment->comment_ID);
					if ( isset(self::$comment_queue[$id_str]) ) {
						// Don't capture twice
						continue;
					}
					// Capture comment Crayons
			        $captures = self::capture_crayons($comment->comment_ID, $comment->comment_content);
			        if ($captures['has_captured'] === TRUE) {
			        	self::$comment_captures[$id_str] = $captures['content'];
			        	self::$comment_queue[$id_str] = array();
				        foreach ($captures['capture'] as $capture_id=>$capture_content) {
				        	self::$comment_queue[$id_str][$capture_id] = $capture_content;
				        }
			        }
			    }
			}
		}
		
		if (!is_admin() && $enqueue && !self::$enqueued) {
			// Crayons have been found and we enqueue efficiently
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
		CrayonLog::debug('enqueue');
		
		global $CRAYON_VERSION;
		wp_enqueue_style('crayon-style', plugins_url(CRAYON_STYLE, __FILE__), array(), $CRAYON_VERSION);
		//wp_enqueue_script('crayon-jquery', plugins_url(CRAYON_JQUERY, __FILE__), array(), $CRAYON_VERSION);
		wp_enqueue_script('crayon-js', plugins_url(CRAYON_JS, __FILE__), array('jquery'), $CRAYON_VERSION);
		wp_enqueue_script('crayon-jquery-popup', plugins_url(CRAYON_JQUERY_POPUP, __FILE__), array('jquery'), $CRAYON_VERSION);
		self::$enqueued = TRUE;
	}
	
	private static function init_special_tags() {
		if (!self::$is_special_tag_init &&
			( CrayonGlobalSettings::val(CrayonSettings::CAPTURE_MINI_TAG) ||
			  CrayonGlobalSettings::val(CrayonSettings::INLINE_TAG) )
			) {
			$aliases = CrayonResources::langs()->ids_and_aliases();
			for ($i = 0; $i < count($aliases); $i++) {
				$alias = $aliases[$i];
				// Support for both block and inline tags
				self::$search_tags[] = '[' . $alias;
				self::$search_tags[] = '{' . $alias;
				
				$alias_regex = CrayonUtil::esc_hash(CrayonUtil::esc_regex($alias));
				if ($i != count($aliases) - 1) {
					$alias_regex .= '|';
				}
				self::$alias_regex .= $alias_regex;
			}
			// Add plain and backquote
			self::$search_tags[] = '[plain';
			self::$search_tags[] = '`';
			self::$is_special_tag_init = TRUE;
		}
	}
	
	// Add Crayon into the_content
	public static function the_content($the_content) {
		CrayonLog::debug('the_content');
		
		global $post;
		// Go through queued posts and find crayons		
		$post_id = strval($post->ID);
		
		if (self::$is_excerpt) {
			// Remove Crayon from content if we are displaying an excerpt
			$excerpt = preg_replace(self::regex_no_capture(), '', $the_content);
			return $excerpt;
		}

		// Find if this post has Crayons
		if ( array_key_exists($post_id, self::$post_queue) ) {
			// XXX We want the plain post content, no formatting
			$the_content_original = $the_content;
			// Replace with IDs now that we need to
			// TODO may replace text changed by other plugins, so not using for now
//			$the_content = self::$post_captures[$post_id];
			// Loop through Crayons
			$post_in_queue = self::$post_queue[$post_id];
			foreach ($post_in_queue as $id=>$v) {
				$atts = $v['atts'];
				$content = $v['code']; // The code we replace post content with
				$crayon = self::shortcode($atts, $content, $id);
				if (is_feed()) { 
					// Convert the plain code to entities and put in a <pre></pre> tag
					$crayon_formatted = CrayonFormatter::plain_code($crayon->code());
				} else {
					// Apply shortcode to the content
					$crayon_formatted = $crayon->output(TRUE, FALSE);
				}
				// Replacing may cause <p> tags to become disjoint with a <div> inside them, close and reopen them if needed
				if (!$crayon->is_inline()) { 
					$the_content = preg_replace_callback('#' . self::REGEX_BETWEEN_PARAGRAPH_SIMPLE . '#msi', 'CrayonWP::add_paragraphs', $the_content);
				}
				// Replace the code with the Crayon
				$the_content = CrayonUtil::preg_replace_escape_back(self::regex_with_id($id), $crayon_formatted, $the_content, 1, $count);
			}
		}
		
		return $the_content;
	}

	public static function comment_text($text) {
		global $comment;
		$comment_id = strval($comment->comment_ID);
		// Find if this post has Crayons
		if ( array_key_exists($comment_id, self::$comment_queue) ) {
			// XXX We want the plain post content, no formatting
			$the_content_original = $text;
			// Loop through Crayons
			$post_in_queue = self::$comment_queue[$comment_id];
			// Replace with IDs now that we need to
			$text = self::$comment_captures[$comment_id];
			
			foreach ($post_in_queue as $id=>$v) {
				$atts = $v['atts'];
				$content = $v['code']; // The code we replace post content with
				$crayon = self::shortcode($atts, $content, $id);
				$crayon_formatted = $crayon->output(TRUE, FALSE);
				// Replacing may cause <p> tags to become disjoint with a <div> inside them, close and reopen them if needed
				if (!$crayon->is_inline()) { 
					$text = preg_replace_callback('#' . self::REGEX_BETWEEN_PARAGRAPH_SIMPLE . '#msi', 'CrayonWP::add_paragraphs', $text);
				}
				// Replace the code with the Crayon
				$text = CrayonUtil::preg_replace_escape_back(self::regex_with_id($id), $crayon_formatted, $text, 1, $text);
			}
		}
		return $text;
	}
	
	public static function add_paragraphs($capture) {
		if (count($capture) != 4) {
			return $capture[0];
		}
		
		// Remove <br/>
		$capture[2] = preg_replace('#' . self::REGEX_BR_BEFORE . '#msi', '$1', $capture[2]);
		$capture[2] = preg_replace('#' . self::REGEX_BR_AFTER . '#msi', '$1', $capture[2]);
		// Add <p>
		$capture[2] = trim($capture[2]);
		
		if (stripos($capture[2], '[crayon') !== 0) {
			$capture[2] = preg_replace('#(\[crayon)#msi', '</p>$1', $capture[2]);
		} else {
			$capture[1] = '';
		}
		
		if ( stripos($capture[2], '[/crayon]') !== strlen($capture[2]) - strlen('[/crayon]') ) {
			$capture[2] = preg_replace('#(\[/crayon\])#msi', '$1<p>', $capture[2]);
		} else {
			$capture[3] = '';
		}
		
		return $capture[1].$capture[2].$capture[3];
	}
	
	// Remove Crayons from the_excerpt
	public static function the_excerpt($the_excerpt) {
		CrayonLog::debug('excerpt');
		
		self::$is_excerpt = TRUE;
		global $post;
		if (!empty($post->post_excerpt)) {
			// Use custom excerpt if defined
			$the_excerpt = wpautop($post->post_excerpt);
		} else {
			// Pass wp_trim_excerpt('') to gen from content (and remove [crayons])
			$the_excerpt = wpautop(wp_trim_excerpt(''));
		}
		self::$is_excerpt = FALSE;
		return $the_excerpt;
	}
	
	/* Capture pre tag and extract settings from the class attribute, if present */
	public static function pre_tag($matches) {
		$pre_class = $matches[1];
		$quotes = $matches[2];
		$class = $matches[3];
		$post_class = $matches[4];
		$atts = $matches[5];
		$content = $matches[6];
		// Strip internal code lines used in Visual Editor
		$content = preg_replace('#<\s*code\b.*?\bclass\s*=\s*"\s*crayon-code-line\s*"[^>]*>(.*?)<\s*/\s*code\s*>#msi', '$1', $content);
		if (!empty($class)) {
			// Allow hyphenated "setting-value" style settings in the class attribute
			$class = preg_replace('#\b([A-Za-z-]+)[_:](\S+)#msi', '$1='.$quotes.'$2'.$quotes, $class);
			return "[crayon $pre_class $class $post_class]{$content}[/crayon]";
		} else {
			return "[crayon $atts]{$content}[/crayon]";
		}
	}
	
	// Check if the $[crayon]...[/crayon] notation has been used to ignore [crayon] tags within posts
	public static function crayon_remove_ignore($the_content) {
		$the_content = str_ireplace(array('$[crayon', 'crayon]$'), array('[crayon', 'crayon]'), $the_content);
		if (CrayonGlobalSettings::val(CrayonSettings::CAPTURE_PRE)) {
			$the_content = str_ireplace(array('$<pre', 'pre>$'), array('<pre', 'pre>'), $the_content);
		}
		if (CrayonGlobalSettings::val(CrayonSettings::PLAIN_TAG)) {
			$the_content = str_ireplace(array('$[plain', 'plain]$'), array('[plain', 'plain]'), $the_content);
		}
		if (CrayonGlobalSettings::val(CrayonSettings::CAPTURE_MINI_TAG) ||
			CrayonGlobalSettings::val(CrayonSettings::INLINE_TAG)) {
			self::init_special_tags();			
			$the_content = preg_replace('#\$([\[\{])('. self::$alias_regex .')#', '$1$2', $the_content);
			$the_content = preg_replace('#('. self::$alias_regex .')([\[\{])\$#', '$1$2', $the_content);
		}
		if (CrayonGlobalSettings::val(CrayonSettings::BACKQUOTE)) {
			$the_content = str_ireplace('\\`', '`', $the_content);
		}
		return $the_content;
	}

	public static function wp_head() {
		CrayonLog::debug('head');
		
		self::$wp_head = TRUE;
		if (!self::$enqueued) {
			// We have missed our chance to check before enqueuing. Use setting to either load always or only in the_post
			CrayonSettingsWP::load_settings(TRUE); // Ensure settings are loaded
			if (!CrayonGlobalSettings::val(CrayonSettings::EFFICIENT_ENQUEUE)) {
				// Efficient enqueuing disabled, always load despite enqueuing or not in the_post
				self::enqueue_resources();
			}
		}
		// Enqueue Theme CSS
		if (CrayonGlobalSettings::val(CrayonSettings::ENQUEUE_THEMES)) {
			self::crayon_theme_css();
		}
		// Enqueue Font CSS
		if (CrayonGlobalSettings::val(CrayonSettings::ENQUEUE_FONTS)) {
			self::crayon_font_css();
		}
	}
	
	public static function crayon_theme_css() {
		global $CRAYON_VERSION;
		$css = CrayonResources::themes()->get_used_css();
		foreach ($css as $theme=>$url) {
			wp_enqueue_style('crayon-theme-'.$theme, $url, array(), $CRAYON_VERSION);
		}
	}
	
	public static function crayon_font_css() {
		global $CRAYON_VERSION;
		$css = CrayonResources::fonts()->get_used_css();
		foreach ($css as $font_id=>$url) {
//			if ($font_id != CrayonFonts::DEFAULT_FONT) {
				wp_enqueue_style('crayon-font-'.$font_id, $url, array(), $CRAYON_VERSION);
//			}
		}
	}
	
	public static function init($request) {
		CrayonLog::debug('init');
		crayon_load_plugin_textdomain();
	}
	
	public static function install() {
		self::update();
	}

	public static function uninstall() {
		
	}
	
	public  static function update() {
		// Upgrade database
		global $CRAYON_VERSION;
		$settings = CrayonSettingsWP::get_settings();
		if ($settings === NULL || !isset($settings[CrayonSettings::VERSION])) {
			return;
		}
		
		$version = $settings[CrayonSettings::VERSION];
		$defaults = CrayonSettings::get_defaults_array();
		$touched = FALSE;
		
		if ($version < '1.7.21') {
			$settings[CrayonSettings::SCROLL] = $defaults[CrayonSettings::SCROLL];
			$touched = TRUE;
		}
		
		if ($version < '1.7.23' && $settings[CrayonSettings::FONT] == 'theme-font') {
			$settings[CrayonSettings::FONT] = $defaults[CrayonSettings::FONT];
			$touched = TRUE;
		}
		
		if ($touched) {
			$settings[CrayonSettings::VERSION] = $CRAYON_VERSION;
			CrayonSettingsWP::save_settings($settings);
		}
	}
	
	public static function basename() {
		return plugin_basename(__FILE__);
	}
	
	public static function wp_load_path() {
		$wp_root_path = str_replace('wp-content/plugins/' . CRAYON_DIR, '', CRAYON_ROOT_PATH);
		return $wp_root_path . 'wp-load.php';
	}
	
}

// Only if WP is loaded and not in admin
if (defined('ABSPATH')) {
	if (!is_admin()) {
		register_activation_hook(__FILE__, 'CrayonWP::install');
		register_deactivation_hook(__FILE__, 'CrayonWP::uninstall');
		
		// Filters and Actions
		add_filter('init', 'CrayonWP::init');
		
		// TODO find a better way to handle updates
		CrayonWP::update();
		CrayonSettingsWP::load_settings(TRUE);
		if (CrayonGlobalSettings::val(CrayonSettings::MAIN_QUERY)) {
			add_action('wp', 'CrayonWP::wp');
		} else {
			add_filter('the_posts', 'CrayonWP::the_posts');
		}
		
		// XXX Some themes like to play with the content, make sure we replace after they're done
		add_filter('the_content', 'CrayonWP::the_content', 100);
		
		if (CrayonGlobalSettings::val(CrayonSettings::COMMENTS)) {
			add_filter('comment_text', 'CrayonWP::comment_text', 100);
		}
		
		add_filter('the_excerpt', 'CrayonWP::the_excerpt');
		add_action('template_redirect', 'CrayonWP::wp_head');
	} else {
		add_action('init', 'CrayonTagEditorWP::init');
	}
}

?>