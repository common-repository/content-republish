=== Content Republish – Easily update and republish your content ===
Author URI: https://www.contentimizer.com
Plugin URI: https://www.contentimizer.com
Tags: republish post, update post, duplicate post, revisions, clone post,
Contributors: yipresser, damienoh
Requires at least: 5.5
Requires PHP: 7.2.5
Tested up to: 6.6.2
Stable tag: 1.1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Content Republish allows you to easily clone your posts, update the content and schedule it for republication.

== Description ==

**An easy way to clone your post, update the content, and republish it without any disruption to the original post.**

Content Republish is the best tool for content publisher. With a single click, you can clone your existing posts, make changes and republish them. It is like a staging area for your draft content, where you can make any changes without affecting the original post. When you are satisfied with the changes, you republish it and the draft content will overwrite the existing content.

## No disruption to your workflow ##

The cloned post retains the same permission as the original post. You can update or edit the content just like normal post.

## User permission ##

Decide which user role is allowed to clone post. For a multi-user site, the administrator can grant the cloning permission to the Editor and then assign it to the Contributor to update the content.

## Supports Gutenberg/Block editor ##

Content Republish works on both the Gutenberg/Block Editor and Classic editor.

## It clones the complete post, not just the content ##

Content Republish doesn't just copy the content, it clones the whole post, including its taxonomies, author, date, custom fields. This means that any plugins that use the taxonomies and custom fields (like Advanced Custom Fields) will continue to work in Content Republish. You can update the category, tags and custom fields too, and they will replace the original content during republication.

## Schedule Future Updates to Posts ##

Do you want your update to go live at a certain time? No problem! Content Republish allows you to schedule your cloned post, and it will republish at the time of your choice.

## Revisions ##

A new revision is created when the post is republished. If you don't like the changes, you can visit the Revisions section to view and restore the original content.

## Convert any post to republish post ##

On a draft post that you are already working on, you can convert it into a republish post.

## Content Republish Pro ##

- Support for custom post types
- Customize settings for individual post before republish
- Receive notifications for every republish
- Customize your own message for notification email.
- Choose addresses you want to exclude from notifications

Themes/Plugins we maintain compatibility with:

* BeaverBuilder integration
* Divi Theme, Divi Builder integration
* Advanced Custom Fields
* Yoast SEO
* SEOPress
* RankMath

[Check out the full features of the Pro version](https://www.contentimizer.com/content-republish/).

## SUPPORT ##

Your feedback is WELCOME!

== Screenshots ==

1. Clone post option in the Posts row action section. Click the "Clone for republish" link to clone the post.
2. Content Republish Settings - configure the user permission and the metadata to copy over when the post is republished.
3. Publish button is changed to "Republish" to avoid confusion.
4. Admin notice message showing at the top of the cloned post to avoid confusion.
5. Scheduled posts are saved with the "Schedule Republish" status to avoid mix up with the regular post.
6. Option to convert a regular draft into a republish post.

== Installation ==

= Using The WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'Content Republish'
3. Click 'Install Now'
4. Activate the plugin on the Plugin dashboard

= Uploading in WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `content-republish.zip` from your computer
4. Click on 'Install Now' button
5. Activate the plugin in the Plugin dashboard

= Using FTP =

1. Download `content-republish.zip`
2. Extract the `content-republish` directory to your computer
3. Upload the `content-republish` folder to the `/wp-content/plugins/` directory
4. Activate the plugin in the Plugin dashboard

== Frequently Asked Questions ==

= Why can't I clone posts that are not published =

Content Republish only work for published posts/pages. You can only clone published posts/pages.

= How do I enable support for custom post types =

Content Republish only work for posts/pages. You can enable custom post types with the Pro version.

= Where are the cloned posts after republish =

The cloned posts are deleted after they were republished. You can select force delete to delete them permanently, or move them to trash, after which they will be removed in 30 days.

== Changelog ==

= 1.1.3 - 03 October 2024 =

* New: Bump to WordPress 6.6.2
* Bug fix: include options to disable support for Page post type.
* Added: usage notice for features only available in the Pro version.

= 1.1.2 - 12 September 2024 =

* Bug fix: Moved inline scripts to external js files
* Bug fix: fixed translation errors

= 1.1.1 - 25 July 2024 =

* Bug fix: added wp_trash_post for cleaning up republished post.

= 1.1.0 - 22 July 2024 =

* New: Code refactoring

= 1.0.1 - 17 July 2024 =

* New: Bump to WordPress 6.6

= 1.0.0 - 6 May 2024 =

* First release

== Upgrade Notice ==

= 1.1.3 =

* Added options to enable/disable Page post type.
* Made compatible with WordPress 6.6.2.
* Added usage notice.