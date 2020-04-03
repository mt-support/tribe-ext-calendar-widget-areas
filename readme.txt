=== The Events Calendar Extension: Calendar Widget Areas ===
Contributors: ModernTribe
Donate link: http://m.tri.be/29
Tags: events, calendar
Requires at least: 4.5
Tested up to: 5.3.2
Requires PHP: 5.6
Stable tag: 1.1.0
License: GPL version 3 or any later version
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Adds widget areas (a.k.a. sidebars) that only display on The Events Calendar pages/views.

== Description ==

Adds widget areas (a.k.a. sidebars) that only display on The Events Calendar pages/views.

Areas may be enabled or disabled at wp-admin > Events > Settings > Display tab > Advanced Template Settings section.

Note that the WP Customizer only allows you to manage widget areas that apply to the page you're currently previewing; therefore, you will need to navigate to your Events page, for example, to edit the content of those widget areas via the Customizer's live preview.

== Installation ==

Install and activate like any other plugin!

* You can upload the plugin zip file via the *Plugins ‣ Add New* screen
* You can unzip the plugin and then upload to your plugin directory (typically _wp-content/plugins_) via FTP
* Once it has been installed or uploaded, simply visit the main plugin list and activate it

== Changelog ==

= [1.1.0] 2020-04-03 =

* Feature - Updated to work with v2 views from The Events Calendar v5.0+ (January 2020).
* Fix - The "TEC Single: Top" widget area was displaying below the single event if the event wasn't part of a recurrence series.
* Fix - Placed the order of options to be the first under the "Advanced Template Settings" section at the bottom of the Display tab, ensuring we don't get in the middle of other options.
* Tweak - Tribe option key changed from `tribe_ext_enabled_widget_areas` to `tribe_ext_calendar_widget_areas_enabled_areas` so you'll need to re-save your _disabled_ widget areas. By default, all widget areas are enabled.
* Tweak - Migrated code to the current Extension Template framework, adding a readme.txt, .pot file, and support for GitHub Updater.
* Tweak - License changed from GPLv2+ to GPLv3+.

= [1.0.1] 2018-01-08 =

* Fix - Extension now conditionally requires the Settings_Helper.php file to prevent the Cannot declare class Tribe__Extension__Settings_Helper because the name is already in use error.

= [1.0.0] 2017-06-29 =

* Initial release.
