<?php
/**
 * Plugin Name: WP-Dapp: Hive Integration
 * Description: A plugin to post content from WordPress to Hive with Keychain support for beneficiaries, tags, and more.
 * Version: 0.7.3
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
define( 'WPDAPP_VERSION', '0.7.3' );
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
wpdapp_safe_include(WPDAPP_PLUGIN_DIR . 'includes/class-hive-api.php');
wpdapp_safe_include(WPDAPP_PLUGIN_DIR . 'includes/class-publish-handler.php');
wpdapp_safe_include(WPDAPP_PLUGIN_DIR . 'includes/class-settings-page.php');
wpdapp_safe_include(WPDAPP_PLUGIN_DIR . 'includes/class-post-meta.php');
wpdapp_safe_include(WPDAPP_PLUGIN_DIR . 'includes/class-ajax-handler.php');
wpdapp_safe_include(WPDAPP_PLUGIN_DIR . 'includes/class-update-checker.php');

/**
 * Plugin version update handler - runs when plugin version changes
 */
function wpdapp_version_update() {
    $current_version = get_option('wpdapp_version', '0.0.0');
    
    // If this is a new installation or update from an older version
    if (version_compare($current_version, WPDAPP_VERSION, '<')) {
        // Get current options
        $options = get_option('wpdapp_options', []);
        
        // Ensure auto_publish is explicitly set to 0 by default
        if (!isset($options['auto_publish'])) {
            $options['auto_publish'] = 0;
            update_option('wpdapp_options', $options);
        }
        
        // Update stored version
        update_option('wpdapp_version', WPDAPP_VERSION);
    }
}
add_action('plugins_loaded', 'wpdapp_version_update', 5); // Priority 5 to run before other init functions

/**
 * Initialize plugin classes on plugins_loaded
 */
function wpdapp_init() {
    // Initialize classes
    new WP_Dapp_Settings_Page();
    new WP_Dapp_Post_Meta();
    new WP_Dapp_Publish_Handler();
    new WP_Dapp_Ajax_Handler();
    
    // Initialize update checker if available
    if (class_exists('WP_Dapp_Update_Checker')) {
        new WP_Dapp_Update_Checker();
    }
}
add_action('plugins_loaded', 'wpdapp_init');

/**
 * Plugin activation hook
 */
function wpdapp_activate() {
    // Set default options
    $default_options = [
        'hive_account' => '',
        'enable_default_beneficiary' => '1',
        'default_beneficiary_account' => 'diggndeeper.com',
        'default_beneficiary_weight' => '100', // 1%
        'default_tags' => 'blog,wordpress',
        'auto_publish' => '0'
    ];
    
    // Only set options if they don't exist
    if (!get_option('wpdapp_options')) {
        add_option('wpdapp_options', $default_options);
    }
    
    // Clear any transients
    delete_transient('wpdapp_update_check');
}
register_activation_hook(__FILE__, 'wpdapp_activate');

/**
 * Plugin deactivation hook
 */
function wpdapp_deactivate() {
    // Clean up transients
    delete_transient('wpdapp_update_check');
}
register_deactivation_hook(__FILE__, 'wpdapp_deactivate');

/**
 * Plugin uninstall hook (static method)
 */
function wpdapp_uninstall() {
    // Get options
    $options = get_option('wpdapp_options', []);
    
    // Delete options and data if requested
    if (!empty($options['delete_data_on_uninstall'])) {
        delete_option('wpdapp_options');
        
        // Delete post meta
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_wpdapp_%'");
    }
}
register_uninstall_hook(__FILE__, 'wpdapp_uninstall');

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
 * 3. Default Tags: Global tags set in the plugin settings
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
 */
