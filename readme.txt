=== Ability Explorer ===
Contributors: (your username)
Tags: abilities, abilities-api, developer, tools, api
Requires at least: 6.9
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Discover, inspect, test, and document all abilities registered via the WordPress 6.9+ Abilities API from core, plugins, and themes.

== Description ==

Ability Explorer is a developer tool that helps you explore and interact with the WordPress Abilities API introduced in WordPress 6.9.

**Features:**

* **Discover Abilities** - View all abilities registered by WordPress core, plugins, and themes
* **Detailed Information** - Inspect input/output schemas, providers, categories, and more
* **Test Runner** - Test abilities with custom input directly from the WordPress admin
* **Demo Abilities** - Optional example abilities to learn how to register your own
* **Search & Filter** - Quickly find abilities by name, slug, provider, or category
* **Developer Friendly** - Clean interface following WordPress design system

**Requirements:**

This plugin requires the WordPress Abilities API to be available. The Abilities API is included in WordPress 6.9 and later.

**For Developers:**

The plugin includes a well-documented example ability (Site Health Status) that demonstrates how to:
* Register abilities and categories
* Define input and output schemas
* Implement execute callbacks
* Set permission callbacks

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/abilitiesexplorer/` directory, or install through the WordPress plugins screen
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to 'Abilities' in the admin menu to start exploring

**Note:** WordPress 6.9 or higher is required for this plugin to function.

== Frequently Asked Questions ==

= What is the Abilities API? =

The Abilities API is a new WordPress core feature introduced in WordPress 6.9 that provides a standardized way to register and execute capabilities/actions within WordPress.

= Do I need to install anything else? =

No additional plugins are required if you're running WordPress 6.9 or higher. The Abilities API is included in WordPress core.

= Can I use this on a production site? =

While the plugin is safe to use, it's primarily intended as a developer tool. The test functionality allows executing abilities, so ensure appropriate user permissions are configured.

= How do I register my own abilities? =

Check out the example ability in `/abilities/site-health.php` which includes detailed comments explaining how to register abilities and categories.

== Screenshots ==

1. Main ability explorer showing all registered abilities
2. Detailed view of an ability with schemas
3. Test runner for invoking abilities with custom input
4. Demo abilities management page

== Changelog ==

= 1.0.0 =
* Initial release
* Ability discovery and listing
* Detailed ability information display
* Test runner for invoking abilities
* Demo Site Health ability
* Search and filter functionality

== Upgrade Notice ==

= 1.0.0 =
Initial release of Ability Explorer for WordPress 6.9+
