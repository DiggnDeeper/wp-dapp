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
        add_action('wp_ajax_wpdapp_verify_keychain', [$this, 'ajax_verify_keychain']);
        add_action('wp_ajax_wpdapp_verify_posts', [$this, 'ajax_verify_posts']);
        add_action('wp_ajax_wpdapp_prepare_post', [$this, 'ajax_prepare_post']);
        add_action('wp_ajax_wpdapp_update_post_meta', [$this, 'ajax_update_post_meta']);
        add_action('wp_ajax_wpdapp_sync_comments', [$this, 'ajax_sync_comments']);
        add_action('wp_ajax_nopriv_wpdapp_sync_comments', [$this, 'ajax_sync_comments']);
        add_action('wp_ajax_wpdapp_render_hive_comments', [$this, 'ajax_render_hive_comments']);
        add_action('wp_ajax_nopriv_wpdapp_render_hive_comments', [$this, 'ajax_render_hive_comments']);
        // Legacy: reset auto_publish was used in previous versions; action removed.
    }

    /**
     * AJAX handler for verifying Hive account with Keychain.
     */
    public function ajax_verify_keychain() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpdapp_verify_credentials')) {
            wp_send_json_error(__('Security check failed', 'wp-dapp'));
        }
        
        // Check if user has permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'wp-dapp'));
        }
        
        // Get data from request
        $account = isset($_POST['account']) ? sanitize_text_field($_POST['account']) : '';
        $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
        $signature = isset($_POST['signature']) ? sanitize_text_field($_POST['signature']) : '';
        
        // Basic validation
        if (empty($account)) {
            wp_send_json_error(__('Hive account name is required', 'wp-dapp'));
        }
        
        if (empty($message) || empty($signature)) {
            wp_send_json_error(__('Signature verification failed', 'wp-dapp'));
        }
        
        // Verify signature with the Hive API
        $hive_api = new WP_Dapp_Hive_API();
        $result = $hive_api->verify_keychain_signature($account, $message, $signature);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        // If we got here, verification was successful
        
        // Save the verified account in options
        $options = get_option('wpdapp_options', []);
        $options['hive_account'] = $account;
        update_option('wpdapp_options', $options);
        
        wp_send_json_success([
            'message' => __('Hive account verified successfully', 'wp-dapp')
        ]);
    }

    /**
     * AJAX handler to verify post publishing status.
     */
    public function ajax_verify_posts() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpdapp_verification')) {
            wp_send_json_error(__('Invalid security token', 'wp-dapp'));
        }
        
        // Check if Hive account is configured
        $options = get_option('wpdapp_options', []);
        $hive_account = !empty($options['hive_account']) ? $options['hive_account'] : '';
        
        if (empty($hive_account)) {
            wp_send_json_error(__('Hive credentials are not configured', 'wp-dapp'));
        }
        
        // Get published posts with Hive meta
        $args = [
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'meta_query' => [
                [
                    'key' => '_wpdapp_processed',
                    'compare' => 'EXISTS'
                ]
            ]
        ];
        
        $query = new WP_Query($args);
        
        if (!$query->have_posts()) {
            wp_send_json_success([]);
        }
        
        $posts_data = [];
        
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $post_title = get_the_title();
            
            $hive_published = get_post_meta($post_id, '_wpdapp_hive_published', true);
            $hive_author = get_post_meta($post_id, '_wpdapp_hive_author', true);
            $hive_permlink = get_post_meta($post_id, '_wpdapp_hive_permlink', true);
            $hive_publish_error = get_post_meta($post_id, '_wpdapp_hive_error', true);
            
            $posts_data[] = [
                'ID' => $post_id,
                'title' => $post_title,
                'edit_url' => get_edit_post_link($post_id, 'raw'),
                'hive_published' => !empty($hive_published),
                'hive_author' => $hive_author,
                'hive_permlink' => $hive_permlink,
                'hive_publish_error' => $hive_publish_error
            ];
        }
        
        wp_reset_postdata();
        
        wp_send_json_success($posts_data);
    }
    
    /**
     * AJAX handler to prepare post data for Keychain publishing.
     */
    public function ajax_prepare_post() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpdapp_publish')) {
            wp_send_json_error(__('Invalid security token', 'wp-dapp'));
        }
        
        // Get post ID
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (!$post_id) {
            wp_send_json_error(__('Invalid post ID', 'wp-dapp'));
        }
        
        // Check if current user can edit this post
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(__('Permission denied', 'wp-dapp'));
        }
        
        // Get post data
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error(__('Post not found', 'wp-dapp'));
        }
        
        // Build tags from categories, tags, and default settings
        $tags = [];
        
        // WordPress categories
        $categories = get_the_category($post_id);
        if (!empty($categories) && !is_wp_error($categories)) {
            foreach ($categories as $cat) {
                if (!empty($cat->slug)) {
                    $tags[] = $cat->slug;
                } else if (!empty($cat->name)) {
                    $tags[] = sanitize_title($cat->name);
                }
            }
        }
        
        // WordPress tags
        $post_tags = wp_get_post_tags($post_id);
        if (!empty($post_tags)) {
            foreach ($post_tags as $tag) {
                $tags[] = sanitize_title($tag->name);
            }
        }
        
        // Default tags from settings
        $options = get_option('wpdapp_options', []);
        if (!empty($options['default_tags'])) {
            $defaults = array_map('trim', explode(',', $options['default_tags']));
            foreach ($defaults as $def) {
                if (!empty($def)) {
                    $tags[] = sanitize_title($def);
                }
            }
        }
        
        // Normalize tags: lowercase, unique, max 5, ensure at least one
        $tags = array_values(array_unique(array_filter(array_map(function($t){
            $t = strtolower($t);
            $t = preg_replace('/[^a-z0-9-]/', '-', $t);
            $t = preg_replace('/-+/', '-', $t);
            return trim($t, '-');
        }, $tags))));
        
        if (empty($tags)) {
            $tags = ['blog'];
        }
        
        $tags = array_slice($tags, 0, 5);
        
        // Get beneficiaries from post meta
        $beneficiaries = get_post_meta($post_id, '_wpdapp_beneficiaries', true);
        
        if (!is_array($beneficiaries)) {
            $beneficiaries = [];
        }
        
        // Get excerpt
        $excerpt = $post->post_excerpt;
        
        if (empty($excerpt)) {
            // Generate excerpt from content
            $excerpt = wp_strip_all_tags($post->post_content);
            $excerpt = wp_trim_words($excerpt, 30, '...');
        }
        
        // Get featured image
        $featured_image_url = '';
        if (has_post_thumbnail($post_id)) {
            $featured_image_id = get_post_thumbnail_id($post_id);
            $image_data = wp_get_attachment_image_src($featured_image_id, 'full');
            
            if ($image_data) {
                $featured_image_url = $image_data[0];
            }
        }
        
        // Format content for Hive (strip Gutenberg blocks, etc.)
        $publish_handler = new WP_Dapp_Publish_Handler();
        $formatted_content = $publish_handler->format_content_for_hive($post->post_content, $post_id);
        
        // Prepare post data for Hive API
        $post_data = [
            'title' => $post->post_title,
            'content' => $formatted_content,
            'tags' => $tags,
            'beneficiaries' => $beneficiaries,
            'excerpt' => $excerpt,
            'featured_image' => $featured_image_url
        ];
        
        // Prepare data with Hive API
        $hive_api = new WP_Dapp_Hive_API();
        $prepared_data = $hive_api->prepare_post_data($post_data);
        
        if (is_wp_error($prepared_data)) {
            wp_send_json_error($prepared_data->get_error_message());
        }
        
        // Mark post as processed
        update_post_meta($post_id, '_wpdapp_processed', 1);
        
        wp_send_json_success($prepared_data);
    }
    
    /**
     * AJAX handler to update post meta after successful Hive publication.
     */
    public function ajax_update_post_meta() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpdapp_publish')) {
            wp_send_json_error(__('Invalid security token', 'wp-dapp'));
        }
        
        // Get post ID and Hive data
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $hive_data = isset($_POST['hive_data']) ? $_POST['hive_data'] : [];
        
        if (!$post_id || empty($hive_data)) {
            wp_send_json_error(__('Invalid data', 'wp-dapp'));
        }
        
        // Check if current user can edit this post
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(__('Permission denied', 'wp-dapp'));
        }
        
        // Save Hive publication data
        update_post_meta($post_id, '_wpdapp_hive_published', 1);
        update_post_meta($post_id, '_wpdapp_hive_author', sanitize_text_field($hive_data['author']));
        update_post_meta($post_id, '_wpdapp_hive_permlink', sanitize_text_field($hive_data['permlink']));
        
        if (!empty($hive_data['transaction_id'])) {
            update_post_meta($post_id, '_wpdapp_hive_transaction_id', sanitize_text_field($hive_data['transaction_id']));
        }
        
        // Clear any legacy auto-publish error
        delete_post_meta($post_id, '_wpdapp_hive_error');
        
        wp_send_json_success([
            'message' => __('Post meta updated successfully', 'wp-dapp')
        ]);
    }

    /**
     * AJAX handler to sync Hive comments into WordPress comments for a given post.
     */
    public function ajax_sync_comments() {
        // Check nonce (works for frontend too)
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpdapp_frontend_sync')) { // Use frontend nonce
            wp_send_json_error(__('Invalid security token', 'wp-dapp'));
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$post_id || get_post_status($post_id) !== 'publish') {
            wp_send_json_error(__('Invalid or unpublished post', 'wp-dapp'));
        }

        // Skip capability check for frontend, but ensure sync is enabled globally
        $options = get_option('wpdapp_options', []);
        if (empty($options['enable_comment_sync'])) {
            wp_send_json_error(__('Comment sync is disabled', 'wp-dapp'));
        }
        $auto_approve = !empty($options['auto_approve_comments']);

        // Ensure post is Hive-published
        $hive_author = get_post_meta($post_id, '_wpdapp_hive_author', true);
        $hive_permlink = get_post_meta($post_id, '_wpdapp_hive_permlink', true);
        if (empty($hive_author) || empty($hive_permlink)) {
            wp_send_json_error(__('This post has not been published to Hive', 'wp-dapp'));
        }

        // Proceed with sync
        $service = new WP_Dapp_Comment_Sync();
        $result = $service->sync_post_comments($post_id, $auto_approve);
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($result);
    }

    /**
     * AJAX handler to render the Hive comments block HTML for a given post.
     */
    public function ajax_render_hive_comments() {
        // Check nonce (reuse frontend sync nonce)
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpdapp_frontend_sync')) {
            wp_send_json_error(__('Invalid security token', 'wp-dapp'));
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$post_id || get_post_status($post_id) !== 'publish') {
            wp_send_json_error(__('Invalid or unpublished post', 'wp-dapp'));
        }

        $options = get_option('wpdapp_options', []);
        if (empty($options['enable_comment_sync'])) {
            wp_send_json_error(__('Comment sync is disabled', 'wp-dapp'));
        }

        $root_author = get_post_meta($post_id, '_wpdapp_hive_author', true);
        $root_permlink = get_post_meta($post_id, '_wpdapp_hive_permlink', true);
        if (empty($root_author) || empty($root_permlink)) {
            wp_send_json_error(__('This post has not been published to Hive', 'wp-dapp'));
        }

        // Render via shortcode
        $html = do_shortcode('[wpdapp_hive_comments post_id="' . intval($post_id) . '"]');
        wp_send_json_success(['html' => $html]);
    }

    // Legacy endpoint removed.
} 