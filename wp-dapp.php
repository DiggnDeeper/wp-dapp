<?php
/**
 * Plugin Name: WP-Dapp: Hive Integration
 * Description: A plugin to post content from WordPress to Hive with support for beneficiaries, tags, and more.
 * Version: 0.2
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
define( 'WPDAPP_VERSION', '0.2' );

// Include required files
require_once WPDAPP_PLUGIN_DIR . 'includes/class-hive-api.php';
require_once WPDAPP_PLUGIN_DIR . 'includes/class-publish-handler.php';
require_once WPDAPP_PLUGIN_DIR . 'includes/class-settings-page.php';
require_once WPDAPP_PLUGIN_DIR . 'includes/class-post-meta.php';

/**
 * Initialize the plugin functionality.
 */
function wpdapp_init() {
    $publish_handler = new WP_Dapp_Publish_Handler();
    $settings_page = new WP_Dapp_Settings_Page();
    $post_meta = new WP_Dapp_Post_Meta();
    
    add_action('publish_post', array($publish_handler, 'on_publish_post'), 10, 2);
}
add_action('plugins_loaded', 'wpdapp_init');

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
            'default_tags' => 'wordpress,hive,wpdapp'
        ]);
    }
}

// Add deactivation hook
register_deactivation_hook(__FILE__, 'wpdapp_deactivate');

function wpdapp_deactivate() {
    // Cleanup tasks if needed
}

// Add uninstall hook - defined in a separate file
register_uninstall_hook(__FILE__, 'wpdapp_uninstall');

function wpdapp_uninstall() {
    // Only delete options if explicitly configured to do so
    $options = get_option('wpdapp_options');
    if (!empty($options['delete_data_on_uninstall'])) {
        delete_option('wpdapp_options');
        
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
