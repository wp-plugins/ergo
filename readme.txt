=== Ergo ===
Contributors: felipeschenone
Donate link: http://felipeschenone.com/donate
Tags: comments, discussion
Requires at least: 3.0
Tested up to: 3.4
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows users to mark their comments as "objections", and from there it builds a system that organizes and rationalizes discussions.

== Description ==

Being justified is being able to defend one's position. Based on this principle, the Ergo plugin enhances the discussion system of your blog. It allows users to mark their comments as "objections", and when they do, the comment or post to which they are replying is marked as having an unanswered objection. If later on, someone answers to your objection, it will become marked as having an unanswered objection, and so the original post or comment will return to having no unanswered objections.

This process may continue indefinitely: a post may have two objections, and each objection may have two objections, and each of those may have two more, and so on. The Ergo plugin keeps track of the discussion and displays the number of unanswered objections next to each post or comment.

In future versions, visual and statistical information about the discussions will be available.

== Installation ==

1. Download and activate the plugin.
2. Edit your theme by inserting `<?php Ergo::widget(); ?>` in the templates for posts and comments. For example, in the TwentyTwelve theme, insert the code in the following files:
	* The `content.php` file.
	* The `twentytwelve_comment` function, in the `functions.php` file.

== Frequently Asked Questions ==

= What happens if I object to a comment that is *not* marked as an objection? =

Then Ergo will asume that the comment you are replying to *is* an objection, but was not marked as so by its author.

= What happens when the discussion reaches the limit of nested comments? =

Then the discussion will stop. The decision as to how deep a discussion can go belongs to the owner of the blog.

== Screenshots ==

1. A post with one unanswered objection.
2. The debate, with one answered objection, and one unanswered objection.
3. The comment form with the option to turn the comment into an objection.
4. The discussion options for a new post.

== Changelog ==

= 1.0 =
* Initial release.