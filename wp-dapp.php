<?php
/**
 * Plugin Name: WP-Dapp: Hive Integration
 * Description: A plugin to post content from WordPress to Hive with support for beneficiaries, tags, and more.
 * Version: 0.6.0
 * Author: DiggnDeeper
 * Author URI: https://diggndeeper.com
 * License: MIT
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define( 'WPDAPP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPDAPP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPDAPP_VERSION', '0.6.0' );
define( 'WPDAPP_REPO_URL', 'https://github.com/DiggnDeeper/wp-dapp' );

/**
 * Safely include a file with error handling
 * 
 * @param string $file File path to include
 * @return bool True if successful, false otherwise
 */
function wpdapp_safe_include($file) {
    if (!file_exists($file)) {
        return false;
    }
    
    try {
        include_once $file;
        return true;
    } catch (Exception $e) {
        // Log the error or handle it silently
        return false;
    }
}

// Include required core files
wpdapp_safe_include(WPDAPP_PLUGIN_DIR . 'includes/class-encryption-utility.php');
wpdapp_safe_include(WPDAPP_PLUGIN_DIR . 'includes/class-hive-api.php');
wpdapp_safe_include(WPDAPP_PLUGIN_DIR . 'includes/class-publish-handler.php');
wpdapp_safe_include(WPDAPP_PLUGIN_DIR . 'includes/class-settings-page.php');
wpdapp_safe_include(WPDAPP_PLUGIN_DIR . 'includes/class-post-meta.php');
wpdapp_safe_include(WPDAPP_PLUGIN_DIR . 'includes/class-ajax-handler.php');
wpdapp_safe_include(WPDAPP_PLUGIN_DIR . 'includes/class-update-checker.php');

/**
 * Initialize the plugin functionality.
 */
function wpdapp_init() {
    $encryption_utility = new WP_Dapp_Encryption_Utility();
    $publish_handler = new WP_Dapp_Publish_Handler();
    // Initialize the settings page (this is the only place it should be initialized)
    $settings_page = new WP_Dapp_Settings_Page();
    $post_meta = new WP_Dapp_Post_Meta();
    $ajax_handler = new WP_Dapp_Ajax_Handler();
    
    // Initialize the simple update checker if it exists
    if (class_exists('WP_Dapp_Update_Checker')) {
        $update_checker = new WP_Dapp_Update_Checker();
    }
    
    add_action('publish_post', array($publish_handler, 'on_publish_post'), 10, 2);
    
    // Make the encryption utility available globally
    global $wpdapp_encryption;
    $wpdapp_encryption = $encryption_utility;
}
add_action('plugins_loaded', 'wpdapp_init');

/**
 * Get the encryption utility instance.
 * 
 * @return WP_Dapp_Encryption_Utility The encryption utility instance.
 */
function wpdapp_get_encryption() {
    global $wpdapp_encryption;
    
    if (!isset($wpdapp_encryption)) {
        $wpdapp_encryption = new WP_Dapp_Encryption_Utility();
    }
    
    return $wpdapp_encryption;
}

// Add activation hook
register_activation_hook(__FILE__, 'wpdapp_activate');

function wpdapp_activate() {
    // Initialize default options
    if (!get_option('wpdapp_options')) {
        add_option('wpdapp_options', [
            'hive_account' => '',
            'private_key' => '',
            'enable_default_beneficiary' => 1,
            'default_beneficiary_account' => 'diggndeeper',
            'default_beneficiary_weight' => 100,  // 1%
            'enable_custom_tags' => 0,
            'default_tags' => 'wordpress,hive,wpdapp',
            'secure_storage' => 1,  // Enable secure storage by default
            'delete_data_on_uninstall' => 0
        ]);
    }
    
    // Generate encryption key if needed
    $encryption = new WP_Dapp_Encryption_Utility();
    
    // Add capabilities
    if (is_admin()) {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Add deactivation hook
register_deactivation_hook(__FILE__, 'wpdapp_deactivate');

function wpdapp_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Add uninstall hook
register_uninstall_hook(__FILE__, 'wpdapp_uninstall');

function wpdapp_uninstall() {
    // Only delete options if explicitly configured to do so
    $options = get_option('wpdapp_options');
    if (!empty($options['delete_data_on_uninstall'])) {
        delete_option('wpdapp_options');
        delete_option('wpdapp_encryption_key');
        delete_option('wpdapp_secure_private_key');
        
        // Delete post meta
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_wpdapp_%'");
        $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_hive_%'");
    }
}

// Add plugin action links
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wpdapp_plugin_action_links');

function wpdapp_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=wpdapp-settings') . '">' . __('Settings', 'wpdapp') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

/**
 * WP-Dapp Tag System Documentation
 * 
 * This plugin handles tags from multiple sources and manages them for Hive publishing.
 * 
 * Tag Sources:
 * 1. WordPress Categories: Automatically converted to Hive tags
 * 2. WordPress Tags: Automatically converted to Hive tags 
 * 3. Custom Tags: Added per post in the Hive Publishing Settings meta box
 * 4. Default Tags: Global tags set in the plugin settings
 * 
 * Tag Processing:
 * - All tags from the different sources are combined
 * - Duplicates are removed (array_unique)
 * - Tags are limited to 5 (Hive's maximum) using array_slice
 * - The first tag becomes the "parent_permlink" in Hive (main category)
 * - If no tags are available, 'blog' is used as a default
 * 
 * Hive Tag Requirements:
 * - Tags must be lowercase letters, numbers, or hyphens
 * - No spaces or special characters allowed
 * - The plugin automatically converts tags to meet these requirements
 * - Maximum of 5 tags per post
 * - At least 1 tag is required
 * 
 * Tag Priority (when limiting to 5):
 * 1. WordPress Categories
 * 2. WordPress Tags
 * 3. Custom Tags from meta box
 * 4. Default tags from settings
 * 
 * To verify tag functionality:
 * 1. Check if post has WordPress categories or tags
 * 2. Verify custom tags in the post meta box
 * 3. Confirm default tags in plugin settings
 * 4. Check Hive publication status in post meta box
 * 
 * Troubleshooting:
 * - If post isn't published to Hive, check for error messages
 * - Verify at least one valid tag exists
 * - Check that "Publish to Hive" is enabled for the post
 */
