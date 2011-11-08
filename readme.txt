=== Plugin Name ===
Contributors: akarmenia
Donate link: http://ak.net84.net/
Tags: syntax highlighter, syntax, highlighter, highlighting, crayon, code highlighter
Requires at least: 3.0
Tested up to: 3.3
Stable tag: 1.5.0

Syntax Highlighter supporting multiple languages, themes, fonts, highlighting from a URL, local file or post text.

== Description ==

A Syntax Highlighter built in PHP and jQuery that supports customizable languages and themes.
It can highlight from a URL, a local file or Wordpress post text. Crayon makes it easy to manage Language files and define
custom language elements with regular expressions.
It also supports some neat features like:

* Toggled plain code
* Toggled line numbers
* Copy/paste code
* Open code in a new window (popup)
* Remote request caching
* Mobile/touchscreen device detection
* Mouse event interaction (showing plain code on double click, toolbar on mouseover)
* Tab sizes
* Code title
* Toggled toolbar
* Striped lines
* Line marking (for important lines)
* Starting line number (default is 1)
* Local directory to search for local files
* File extension detection
* Live Preview in settings
* Dimensions, margins, alignment and CSS floating
* Extensive error logging

**Supported Languages**

Languages are defined in language files using Regular Expressions to capture elements.
See http://ak.net84.net/projects/crayon-language-file-specification/

* Default Langauge (one size fits all, highlights generic code)
* C
* C#
* C++
* CSS
* HTML (XML/XHTML)
* Java
* JavaScript
* Objective-C
* PHP
* Python
* Shell (Unix)
* Visual Basic

Live Demo: <a href="http://bit.ly/poKNqs" target="_blank">http://bit.ly/poKNqs</a>

Short How-To: <a href="http://ak.net84.net/projects/crayon-syntax-highlighter/" target="_blank">http://ak.net84.net/projects/crayon-syntax-highlighter/</a>

**Planned Features**

* Translations
* Multiple highlighting per Crayon
* Highlighting priority
* Theme Editor

== Installation ==

Download the .zip of the plugin and extract the contents. Upload it to the Wordpress plugin directory and activate the plugin.
You can change settings and view help under <strong>Settings > Crayon</strong> in the Wordpress Admin.

== Frequently Asked Questions ==

= How do I use this thing? =

<code>[crayon lang="php"] your code [/crayon]</code>
<code>[crayon url="http://example.com/code.txt" /]</code>
<code>[crayon url="/local-path-defined-in-settings/code.java" /]</code>

Please see the contextual help under <strong>Settings > Crayon</strong> for quick info about languages, themes, etc.

= I need help, now! =

Contact me at http://twitter.com/crayonsyntax or crayon.syntax@gmail.com.

== Screenshots ==

1. Classic theme in Live Preview under Settings > Crayon.
2. Twilight theme.

== Changelog ==

= 1.5.0 =
* Added ability to cache remote code requests for a set period of time to reduce server load. See Settings > Crayon > Misc. You can clear the cache at any time in settings. Set the cache clearing interval to "Immediately" to prevent caching.
* Fixed a bug preventing dropdown settings from being set correctly
* Fixed AJAX settings bug
* Fixed CSS syntax bug for fonts
* Improved code popup, strips style atts
* Added preview code for shell, renamed to 'Shell'
* Code popup window now shows either highlighted or plain code, depending on which is currently visible

= 1.4.4 =
* Revised CSS style printing
* Fixed bugs with the "open in new window" and copy/paste actions
* Upgraded jQuery to 1.7

= 1.4.3 =
* Fixed a bug that caused the help info to remain visible after settings submit

= 1.4.2 =
* Huge overhaul of Crayon detection and highlighting
* IDs are now added to Crayons before detection
* No more identification issues possible
* Highlighting grabs the ID and uses it in JS
* Only detects Crayons in visible posts, performance improved
* This fixes issues with <!--nextpage-->

= 1.4.1 =
* Fixed Preview in settings, wasn't loading code from different languages
* Fixed code toggle button updating for copy/paste
* Added some keywords to c++, changed sample code

= 1.4.0 =
* Added all other global settings for easy overriding: http://ak.net84.net/projects/crayon-settings/
* Fixed issues with variables and entites in language regex
* Added Epicgeeks theme made by Joe Newing of epicgeeks.net 
* Help updated
* Fixed notice on missing jQuery string in settings
* Reduced number of setting reads
* Setting name cleanup
* Added a donate button, would appreciate any support offered and I hope you find Crayon useful
* String to boolean in util fixed

= 1.3.5 =
* Removed some leftover code from popupWindow

= 1.3.4 =
* Added the ability to open the Crayon in an external window for Mobile devices, originally thought it wouldn't show popup  

= 1.3.3 =
* Added the ability to open the Crayon in an external window

= 1.3.2 =
* Added missing copy icon

= 1.3.1 =
* This fixes an issue that was not completely fixed in 1.3.0:
* Removed the lookbehind condition for escaping $ and \ for backreference bug 

= 1.3.0 =
* Recommended upgrade for everyone.
* Major bug fix thanks to twitter.com/42dotno and twitter.com/eriras
* Fixed a bug causing attributes using single quotes to be undetected
* Fixed a bug causing code with dollar signs followed by numbers to be detected as backreferences and replace itself!
* Fixed a bug causing formatting to be totally disregarded.
* Fixed the <!--more--> tag in post_content and the_excerpt by placing crayon detection after all other formatting has taken place
* Added copy and paste, didn't use flash, selects text and asks user to copy (more elegant until they sort out clipboard access)
* Added shell script to languages - use with lang='sh'
* Removed certain usage of heredocs and replaced with string concatenation
* Added 'then' to default statements
* Cleaned up processing of post_queue used for Crayon detection and the_excerpt
* Added focus to plain text to allow easier copy-paste

= 1.2.3 =
* Prevented Crayons from appearing as plain text in excerpts
http://wordpress.org/support/topic/plugin-crayon-syntax-highlighter-this-plugin-breaks-the-tag

= 1.2.2 =
* Fixed the regex for detecting python docstrings. It's a killer, but it works!
(?:(?<!\\)""".*?(?<!\\)""")|(?:(?<!\\)'''.*?(?<!\\)''')|((?<!\\)".*?(?<!\\)")|((?<!\\)'.*?(?<!\\)')

= 1.2.1 =
* Added the feature to specify the starting line number both globally in settings and also using the attribute:
** [crayon start-line="1234"]fun code[/crayon]
* Thanks for the suggestion from travishill:
** http://wordpress.org/support/topic/plugin-crayon-syntax-highlighter-add-the-ability-to-specify-starting-line-number?replies=2#post-2389518

= 1.2.0 =
* Recommended upgrade for everyone.
* Fixed crucial filesystem errors for Windows regarding filepaths and resource loading
* Said Windows bug was causing Live Preview to fail, nevermore.
* Fixed loading based on URL structure that caused wp_remote errors and local paths not being recognised
* Removed redundant dependency on filesystem path slashes
* PHP now fades surrounding HTML

= 1.1.1 =
* Plugin version information is updated automatically

= 1.1.0 =
* Recommended upgrade for everyone running 1.0.3.
* Fixes a bug that causes code become unhighlighted
* Attribute names can be given in any case in shortcodes  
* Fixes settings bug regarding copy constructor for locked settings
* Minor bug fixes and cleanups

= 1.0.3 =
* Added highlight="false" attribute to temporarily disable highlighting.
* Fixed default color of font for twilight font.

= 1.0.2 =
* Minor bug fixes.

= 1.0.1 =
* Fixed a bug that caused Themes not to load for some Crayons due to Wordpress content formatting.

= 1.0.0 =
* Initial Release. Huzzah!

== Upgrade Notice ==

No issues upgrading.
