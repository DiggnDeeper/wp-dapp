=== WP-Dapp: Hive Integration ===
Contributors: diggndeeper.com
Tags: hive, blockchain, publishing, crypto, social
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 0.7.3
License: MIT
License URI: https://opensource.org/licenses/MIT

A WordPress plugin to publish content to the Hive blockchain with Hive Keychain support for secure authentication and transactions.

== Description ==

WP-Dapp is a WordPress plugin that enables publishing content to the Hive blockchain directly from your WordPress dashboard using the secure Hive Keychain browser extension.

**Key Features:**

* Secure authentication with Hive Keychain (no private keys stored in WordPress)
* Publish WordPress posts automatically to Hive
* Auto-Publish feature to streamline the WordPress to Hive publishing workflow
* Set up default or custom beneficiaries for each post
* Assign tags to your content
* Simple, intuitive settings interface

**Hive Keychain Integration:**

This plugin integrates with the Hive Keychain browser extension to provide enhanced security and ease of use:

* Your private keys never leave your browser and are never stored by the plugin
* One-click verification of your Hive account
* Review and approve each publication to Hive with a simple popup
* No need to enter passwords or keys when publishing

**Auto-Publish Feature:**

* Optionally enable automatic detection of newly published WordPress posts
* Intentionally disabled by default (opt-in only) for security and control
* Get notified when a post is ready for Hive publication
* Streamline your publishing workflow while maintaining secure Keychain authentication
* Each post maintains a visible indicator when it's ready for Hive publication

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wp-dapp` directory, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Configure the plugin settings under Settings -> WP-Dapp.
4. Enter your Hive account information and customize the posting options.

== Frequently Asked Questions ==

= Is my private key secure? =

Yes. With Hive Keychain integration, your private keys never leave your browser and are never stored by the plugin. Keychain securely handles all blockchain operations without exposing your keys.

= Can I choose which posts are published to Hive? =

Yes. Each post has publishing options that allow you to control whether it gets published to Hive.

= What is the Auto-Publish feature? =

The Auto-Publish feature automatically marks WordPress posts for Hive publication when they are published. You still need to complete the publishing process with Hive Keychain, but the system will notify you when posts are ready to be published to Hive.

= How do I update the plugin? =

The plugin includes a simple update checker that will notify you when a new version is available on GitHub. Follow the link in the notification to download the latest version.

== Changelog ==

= 0.7.3 =
* Added: Auto-Publish feature to streamline WordPress to Hive publishing workflow
* Added: UI indicators for posts ready for Hive publishing
* Added: Notification system for posts marked for Auto-Publish
* Improved: Settings page with Auto-Publish option
* Added: Comprehensive documentation for the Auto-Publish feature

= 0.7.2 =
* Fixed: Removed WordPress Gutenberg block comments (<!-- wp:paragraph --> etc.) from Hive published content
* Improved content formatting for Hive posts to ensure cleaner appearance

= 0.7.1 =
* Fixed Hive Keychain detection issue with more robust detection
* Fixed beneficiary percentage validation to properly support decimal values
* Improved input validation for beneficiary percentages
* Added better error handling when Keychain is installed but not detected immediately

= 0.7.0 =
* Initial release on dapp.diggndeeper.com
* Added full Hive Keychain integration for enhanced security
* Removed dependency on stored private keys
* Improved authentication and transaction signing process
* Enhanced error handling for Keychain interactions
* Updated documentation with comprehensive Keychain integration instructions

= 0.6.1 =
* Fixed credentials error in Publication Verification feature
* Improved error messaging for credential verification
* Enhanced user experience with better error feedback

= 0.6.0 =
* Added Publication Verification feature to check post publishing status
* Created comprehensive USER_GUIDE.md documentation
* Added detailed tag system documentation
* Improved error handling and user feedback

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

= 0.7.0 =
Major security enhancement: This update adds full Hive Keychain integration, eliminating the need to store private keys in WordPress. For the best security, please install the Hive Keychain browser extension after upgrading.

= 0.5.0 =
This update removes the complex GitHub updater component and replaces it with a simpler notification system, improving stability across different hosting environments. 