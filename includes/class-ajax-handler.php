<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class WP_Dapp_Ajax_Handler
 * 
 * Handles AJAX requests for the WP-Dapp plugin.
 */
class WP_Dapp_Ajax_Handler {

    /**
     * Constructor.
     */
    public function __construct() {
        // Register AJAX actions
        add_action('wp_ajax_wpdapp_verify_credentials', [$this, 'ajax_verify_credentials']);
        add_action('wp_ajax_wpdapp_verify_posts', [$this, 'ajax_verify_posts']);
    }

    /**
     * AJAX handler for verifying Hive credentials.
     */
    public function ajax_verify_credentials() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpdapp_verify_credentials')) {
            wp_send_json_error('Security check failed');
        }
        
        // Check if user has permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        // Get credentials from request
        $account = isset($_POST['account']) ? sanitize_text_field($_POST['account']) : '';
        $key = isset($_POST['key']) ? sanitize_text_field($_POST['key']) : '';
        $secure_storage = isset($_POST['secure_storage']) ? (bool)$_POST['secure_storage'] : false;
        
        // Basic validation
        if (empty($account)) {
            wp_send_json_error('Hive account name is required');
        }
        
        // If secure storage is enabled and no new key provided, try to get the stored key
        if ($secure_storage && empty($key)) {
            $encryption = wpdapp_get_encryption();
            $key = $encryption->get_secure_option('wpdapp_secure_private_key');
            
            if (empty($key)) {
                wp_send_json_error('Private key not found. Please enter your private key.');
            }
        }
        
        if (empty($key)) {
            wp_send_json_error('Private posting key is required');
        }
        
        // Verify credentials
        $hive_api = new WP_Dapp_Hive_API();
        $result = $hive_api->verify_credentials($account, $key);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        // If we got here, credentials are valid
        
        // If secure storage is enabled, store the key securely
        if ($secure_storage && !empty($key)) {
            $encryption = wpdapp_get_encryption();
            $encryption->store_secure_option('wpdapp_secure_private_key', $key);
            
            // Update options to use secure storage
            $options = get_option('wpdapp_options', []);
            $options['secure_storage'] = 1;
            $options['private_key'] = ''; // Clear plaintext key
            update_option('wpdapp_options', $options);
        }
        
        wp_send_json_success([
            'message' => 'Credentials verified successfully'
        ]);
    }

    /**
     * AJAX handler to verify post publishing status.
     */
    public function ajax_verify_posts() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpdapp_verification')) {
            wp_send_json_error('Invalid security token');
        }
        
        // Check if Hive credentials are configured
        $options = get_option('wpdapp_options', []);
        $hive_account = !empty($options['hive_account']) ? $options['hive_account'] : '';
        $has_key = false;
        
        // Check for key in either secure storage or plaintext
        if (!empty($options['secure_storage'])) {
            $encryption = wpdapp_get_encryption();
            $has_key = !empty($encryption->get_secure_option('wpdapp_secure_private_key'));
        } else {
            $has_key = !empty($options['private_key']);
        }
        
        if (empty($hive_account) || !$has_key) {
            wp_send_json_error('Hive credentials are not configured. Please configure your Hive account and private key in the settings.');
        }
        
        // Get posts with Hive metadata
        $args = [
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => '_hive_published',
                    'compare' => 'EXISTS',
                ],
                [
                    'key' => '_hive_publish_error',
                    'compare' => 'EXISTS',
                ],
                [
                    'key' => '_wpdapp_publish_to_hive',
                    'compare' => 'EXISTS',
                ]
            ]
        ];
        
        $query = new WP_Query($args);
        
        $result = [];
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $result[] = [
                    'ID' => $post_id,
                    'title' => get_the_title(),
                    'edit_url' => get_edit_post_link($post_id, ''),
                    'hive_published' => get_post_meta($post_id, '_hive_published', true),
                    'hive_permlink' => get_post_meta($post_id, '_hive_permlink', true),
                    'hive_author' => get_post_meta($post_id, '_hive_author', true),
                    'hive_publish_error' => get_post_meta($post_id, '_hive_publish_error', true),
                    'wpdapp_publish_to_hive' => get_post_meta($post_id, '_wpdapp_publish_to_hive', true)
                ];
            }
            
            wp_reset_postdata();
        }
        
        wp_send_json_success($result);
    }
} 