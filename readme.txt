=== Published By ===
Contributors: coffee2code
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=6ARCFJ9TX3522
Tags: post, publish, publisher, editor, author, audit, auditing, tracking, coffee2code
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 3.6
Tested up to: 4.1
Stable tag: 1.0.2

Track which user actually published a post, separate from who created the post. Display that info as a column in admin post listings.

== Description ==

This plugin records which user actually published a post, which in a multi-author environment may not always be the original post author. This helps to maintain accountability for who was ultimately responsible for a post appearing live on a site.

The admin listing of posts is amended with a new "Published By" column that shows the name of the person who published the post (for those posts that have actually been published).

For posts that were published prior to the use of this plugin (and thus the plugin could not directly record who published those posts), the plugin makes a best guess attempt to ascertain who published the post. After failing to find the publisher of the post as recorded by the plugin, it checks for who last edited the post, then who is responsible for the latest revision of the post, and finally failing those, it assumes it was the post author. If you'd rather the plugin not make an attempt to guess the publisher, you can disable the checks by including this snippet in your theme's functions.php (or, ideally, a site-specific mu-plugin):

`<?php add_filter( 'c2c_published_by_skip_guessing', '__return_true' ); ?>`


Links: [Plugin Homepage](http://coffee2code.com/wp-plugins/published-by/) | [Plugin Directory Page](https://wordpress.org/plugins/published-by/) | [Author Homepage](http://coffee2code.com)


== Installation ==

1. Unzip `published-by.zip` inside the plugins directory for your site (typically `/wp-content/plugins/`). Or install via the built-in WordPress plugin installer)
2. Activate the plugin through the 'Plugins' admin menu in WordPress


== Screenshots ==

1. A screenshot of the admin post listing showing the added "Published By" column.
2. A screenshot of the Publish metabox for a published post showing who published the post.


== Frequently Asked Questions ==

= If a post is published, then changed back to a draft, and then published a second time by a different user, who is noted as the publishing user? =

The user most recently responsible for a post getting published will be recorded as the publishing user. Editing a published post does not change the publishing user.

= Does this plugin include unit tests? =

Yes.


== Changelog ==

= 1.0.2 (2015-02-17) =
* Minor additions to unit tests
* Use __DIR__ instead of `dirname(__FILE__)`
* Note compatibility through WP 4.1+
* Update copyright date (2015)
* Regenerate .pot

= 1.0.1 (2014-08-25) =
* Minor amendment to documentation
* Minor tweak to an FAQ question
* Change documentation links to wp.org to be https
* Change donate link
* Note compatibility through WP 4.0+
* Add plugin icon

= 1.0 =
* Initial public release


== Upgrade Notice ==

= 1.0.2 =
Trivial update: minor additions to unit tests; noted compatibility through WP 4.1+; updated copyright date (2015)

= 1.0.1 =
Trivial update: noted compatibility through WP 4.0+; added plugin icon.

= 1.0 =
Initial public release.
