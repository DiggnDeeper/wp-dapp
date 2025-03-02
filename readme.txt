=== WP-Dapp: Hive Integration ===
Contributors: diggndeeper
Tags: hive, blockchain, publishing, crypto, social
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 0.6.0
License: MIT
License URI: https://opensource.org/licenses/MIT

A WordPress plugin to publish content to the Hive blockchain with support for beneficiaries, tags, and more.

== Description ==

WP-Dapp is a WordPress plugin that enables publishing content to the Hive blockchain directly from your WordPress dashboard.

**Key Features:**

* Publish WordPress posts automatically to Hive
* Set up default or custom beneficiaries for each post
* Assign tags to your content
* Secure storage of your Hive private posting key
* Simple, intuitive settings interface

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wp-dapp` directory, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Configure the plugin settings under Settings -> WP-Dapp.
4. Enter your Hive account information and customize the posting options.

== Frequently Asked Questions ==

= Is my private key secure? =

Yes. By default, your private posting key is stored securely using WordPress's encryption system.

= Can I choose which posts are published to Hive? =

Yes. Each post has publishing options that allow you to control whether it gets published to Hive.

= How do I update the plugin? =

The plugin includes a simple update checker that will notify you when a new version is available on GitHub. Follow the link in the notification to download the latest version.

== Changelog ==

= 0.5.0 =
* Replaced complex GitHub updater with a simplified update notification system
* Improved plugin stability and compatibility with various hosting environments
* Removed dependency on external libraries for update checking
* Added clear admin notices when updates are available

= 0.4.4 =
* Fixed "PUC does not support updates for plugins hosted on GitHub" error
* Added direct VCS component initialization to bypass factory issues
* Enhanced error handling and diagnostics for update system
* Improved compatibility with various hosting environments

= 0.4.3 =
* Fixed critical error with Plugin Update Checker library
* Improved namespacing compatibility for updater component
* Enhanced error handling to prevent fatal errors

= 0.4.2 =
* Enhanced plugin activation safety
* Added safe file loading and error handling
* Improved compatibility with different WordPress configurations

= 0.4.1 =
* Fixed GitHub updater compatibility issues
* Improved error handling for updater

= 0.4 =
* Added GitHub-based automatic updates
* Fixed duplicate settings page in admin menu
* General code improvements

= 0.3 =
* Initial public release
* Core functionality for publishing to Hive
* Settings page for configuration
* Post meta box for Hive publishing options

== Upgrade Notice ==

= 0.5.0 =
This update removes the complex GitHub updater component and replaces it with a simpler notification system, improving stability across different hosting environments. 