=== HRV MLA Plugin ===
Tags: comments, spam
Requires at least: 3.0.1
Tested up to: 6
Stable tag: 5.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Here is a short description of the plugin.  This should be no more than 150 characters.  No markup here.

== Description ==

This is the long description.  No limit, and you can use Markdown (as well as in the following sections).

For backwards compatibility, if this section is missing, the full length of the short description will be used, and
Markdown parsed.

A few notes about the sections above:

*   "Contributors" is a comma separated list of wp.org/wp-plugins.org usernames
*   "Tags" is a comma separated list of tags that apply to the plugin
*   "Requires at least" is the lowest version that the plugin will work on
*   "Tested up to" is the highest version that you've *successfully used to test the plugin*. Note that it might work on
higher versions... this is just the highest one you've verified.
*   Stable tag should indicate the Subversion "tag" of the latest stable version, or "trunk," if you use `/trunk/` for
stable.

    Note that the `readme.txt` of the stable tag is the one that is considered the defining one for the plugin, so
if the `/trunk/readme.txt` file says that the stable tag is `4.3`, then it is `/tags/4.3/readme.txt` that'll be used
for displaying information about the plugin.  In this situation, the only thing considered from the trunk `readme.txt`
is the stable tag pointer.  Thus, if you develop in trunk, you can update the trunk `readme.txt` to reflect changes in
your in-development version, without having that information incorrectly disclosed about the current stable version
that lacks those changes -- as long as the trunk's `readme.txt` points to the correct stable tag.

    If no stable tag is provided, it is assumed that trunk is stable, but you should specify "trunk" if that's where
you put the stable version, in order to eliminate any doubt.

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload `hrv_mla.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `<?php do_action('plugin_name_hook'); ?>` in your templates

== Frequently Asked Questions ==

= A question that someone might have =

An answer to that question.

= What about foo bar? =

Answer to foo bar dilemma.

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
2. This is the second screen shot

== Changelog ==

= 2.2.407 =
* Fix Capture bug

= 2.2.405 =
* Capture payment after 4 days

= 2.2.405 =
* Capture payment after 3 days

= 2.2.402 =
* Stripe to manual charge

= 2.2.400 =
* Refactor commission pricing

= 2.2.400 =
* Removed debugging and change gbp to usd

= 2.2.304 =
* fix search 


= 2.2.302 =
* whole number

= 2.2.301 =
* Add resort to search and remove guest email in booking

= 2.2.300 =
* New update

= 2.2.26 =
* Add extras

= 2.2.25 =
* Add results to localstorage

= 2.2.24 =
* ACF and decimal issue

= 2.2.231 =
* Stripe deposit in ACF Option

= 2.2.23 =
* Add Deposit Constant in Admin Class

= 2.2.21 =
* Responsive styles

= 2.2.22 =
* Add Email to Golf Booking company
* Fix responsive booking form


= 2.2.21 =
* Add short content on search results with dates

= 2.2.20 =
* Add short content on search results

= 2.2.19 =
* Fix pricing values when booking in the fronend

= 2.2.18 =
* Fix pricing values when booking in the fronend

= 2.2.17 =
* Changed game room icon
* Changed pounds to dollars
* Fix $ color in booking summary

= 2.2.16 =
* Removed amenities styling

= 2.2.15 =
* Add Amenities shortocde

= 2.2.14 =
* Add Amenities to the results page 

= 2.2.13 =
* Add Extra items on manual booking

= 2.2.12 =
* Add Extra price manual booking

= 2.2.11 =
* Change Booking Title

= 2.2.10 =
* Add booking summary

= 2.2.9 =
* Change booking title

= 2.2.81 =
* add email pricing

= 2.2.8 =
* add price on check availability

= 2.2.7 =
* fix pricing bug 2

= 2.2.6 =
* fix pricing bug

= 2.2.54 =
* hotfix

= 2.2.53 =
* add debug

= 2.2.52 =
* Owner email calculation fix

= 2.2.51 =
* Calendar hotfix

= 2.2.5 =
* Fix Calendar on Contact page
* Fix Email templates on API pricing

= 2.2.4 =
* Ciirus API pricing fix

= 2.2.3 =
* Fix FAQ Tabs

= 2.2.2 =
* Add Date picker to other date fields

= 2.2.1 =
* Fix Mac select view

= 2.2.0 =
* Fix addon email text alignment

= 2.1.9 =
* Change updater

= 2.1.8 =
* New version

= 2.1.7 =
* New version

= 1.0 =
* A change since the previous version.
* Another change.

= 0.5 =
* List versions from most recent at top to oldest at bottom.

== Upgrade Notice ==

= 1.0 =
Upgrade notices describe the reason a user should upgrade.  No more than 300 characters.

= 0.5 =
This version fixes a security related bug.  Upgrade immediately.

== Arbitrary section ==

You may provide arbitrary sections, in the same format as the ones above.  This may be of use for extremely complicated
plugins where more information needs to be conveyed that doesn't fit into the categories of "description" or
"installation."  Arbitrary sections will be shown below the built-in sections outlined above.

== A brief Markdown Example ==

Ordered list:

1. Some feature
1. Another feature
1. Something else about the plugin

Unordered list:

* something
* something else
* third thing

Here's a link to [WordPress](http://wordpress.org/ "Your favorite software") and one to [Markdown's Syntax Documentation][markdown syntax].
Titles are optional, naturally.

[markdown syntax]: http://daringfireball.net/projects/markdown/syntax
            "Markdown is what the parser uses to process much of the readme file"

Markdown uses email style notation for blockquotes and I've been told:
> Asterisks for *emphasis*. Double it up  for **strong**.

`<?php code(); // goes in backticks ?>`