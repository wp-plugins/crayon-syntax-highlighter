=== Plugin Name ===
Contributors: akarmenia
Donate link: http://ak.net84.net/
Tags: syntax highlighter, syntax, highlighter, highlighting, crayon, code highlighter
Requires at least: 3.0
Tested up to: 3.3
Stable tag: 1.3

Syntax Highlighter supporting multiple languages, themes, highlighting from a URL, local file or post text.

== Description ==

A Syntax Highlighter built in PHP and jQuery that supports customizable languages and themes.
It can highlight from a URL, a local file or Wordpress post text. Crayon makes it easy to manage Language files and define
custom language elements with regular expressions.
It also supports some neat features like mobile/touchscreen device detection, mouse interactions, toggled plain code, toggled line numbers, tab sizes, error logging and file extension detection just to name a few.

Live Demo: <a href="http://bit.ly/poKNqs" target="_blank">http://bit.ly/poKNqs</a>

Short How-To: <a href="http://ak.net84.net/projects/crayon-syntax-highlighter/" target="_blank">http://ak.net84.net/projects/crayon-syntax-highlighter/</a>

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

1. Different themes and Live Preview under Settings > Crayon.

== Changelog ==

= 1.3 =
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
