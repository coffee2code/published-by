=== Published By ===
Contributors: coffee2code
Donate link: http://coffee2code.com/donate
Tags: post, publish, publisher, editor, author, audit, auditing, tracking, coffee2code
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 3.6
Tested up to: 3.8
Stable tag: 1.0

Track which user actually published a post, separate from who created the post. Display that info as a column in admin post listings.

== Description ==

This plugin records which user actually published a post, which in a multi-author environment may not always be the original post author. This helps to maintain accountability for who was ultimately responsible for a post appearing live on a site.

The admin listing of posts is amended with a new "Published By" column that shows the name of the person who published the post (for those posts that have actually been published).

Links: [Plugin Homepage](http://coffee2code.com/wp-plugins/published-by/) | [Plugin Directory Page](http://wordpress.org/plugins/published-by/) | [Author Homepage](http://coffee2code.com)


== Installation ==

1. Unzip `published-by.zip` inside the plugins directory for your site (typically `/wp-content/plugins/`). Or install via the built-in WordPress plugin installer)
2. Activate the plugin through the 'Plugins' admin menu in WordPress


== Screenshots ==

1. A screenshot of the admin post listing showing the added "Published By" column.
2. A screenshot of the Publish metabox for a published post showing who published the post.


== Frequently Asked Questions ==

= If a post is published, then changed back to a draft, and then published a second time, who is noted as the publishing user? =

The user most recently responsible for a post getting published will be recorded as the publishing user. Editing a published post does not change the publishing user.

= Does this plugin include unit tests? =

Yes.


== Changelog ==

= 1.0 =
* Initial public release


== Upgrade Notice ==

= 1.0 =
Initial public release.
