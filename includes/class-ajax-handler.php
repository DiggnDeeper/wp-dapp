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
        // Legacy: reset auto_publish was used in previous versions; action removed.
    }

    /**
     * AJAX handler for verifying Hive account with Keychain.
     */
    public function ajax_verify_keychain() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpdapp_verify_credentials')) {
            wp_send_json_error('Security check failed');
        }
        
        // Check if user has permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        // Get data from request
        $account = isset($_POST['account']) ? sanitize_text_field($_POST['account']) : '';
        $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
        $signature = isset($_POST['signature']) ? sanitize_text_field($_POST['signature']) : '';
        
        // Basic validation
        if (empty($account)) {
            wp_send_json_error('Hive account name is required');
        }
        
        if (empty($message) || empty($signature)) {
            wp_send_json_error('Signature verification failed');
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
            'message' => 'Hive account verified successfully'
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
        
        // Check if Hive account is configured
        $options = get_option('wpdapp_options', []);
        $hive_account = !empty($options['hive_account']) ? $options['hive_account'] : '';
        
        if (empty($hive_account)) {
            wp_send_json_error('Hive credentials are not configured');
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
            wp_send_json_error('Invalid security token');
        }
        
        // Get post ID
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (!$post_id) {
            wp_send_json_error('Invalid post ID');
        }
        
        // Check if current user can edit this post
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Permission denied');
        }
        
        // Get post data
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error('Post not found');
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
            wp_send_json_error('Invalid security token');
        }
        
        // Get post ID and Hive data
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $hive_data = isset($_POST['hive_data']) ? $_POST['hive_data'] : [];
        
        if (!$post_id || empty($hive_data)) {
            wp_send_json_error('Invalid data');
        }
        
        // Check if current user can edit this post
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Permission denied');
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
            'message' => 'Post meta updated successfully'
        ]);
    }

    /**
     * AJAX handler to sync Hive comments into WordPress comments for a given post.
     */
    public function ajax_sync_comments() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpdapp_publish')) {
            wp_send_json_error('Invalid security token');
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$post_id) {
            wp_send_json_error('Invalid post ID');
        }

        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Permission denied');
        }

        // Option check: ensure comment sync is enabled
        $options = get_option('wpdapp_options', []);
        $comment_sync_enabled = !empty($options['enable_comment_sync']);
        if (!$comment_sync_enabled) {
            wp_send_json_error('Comment sync is disabled in WP-Dapp settings');
        }
        $auto_approve = !empty($options['auto_approve_comments']);

        // Ensure this post has Hive mapping
        $hive_author = get_post_meta($post_id, '_wpdapp_hive_author', true);
        $hive_permlink = get_post_meta($post_id, '_wpdapp_hive_permlink', true);
        if (empty($hive_author) || empty($hive_permlink)) {
            wp_send_json_error('This post has not been published to Hive');
        }

        $hive_api = new WP_Dapp_Hive_API();
        $replies = $hive_api->get_all_replies($hive_author, $hive_permlink, 0, 2000);
        if (is_wp_error($replies)) {
            wp_send_json_error($replies->get_error_message());
        }

        $imported = 0;
        $skipped = 0;
        $map_hive_to_wp = [];

        // Build an index of existing Hive comment IDs to avoid duplicates
        // We use comment meta key _wpdapp_hive_comment_key = author/permlink
        $existing_index = [];
        $existing = get_comments([
            'post_id' => $post_id,
            'status' => 'all',
            'meta_key' => '_wpdapp_hive_comment_key',
            'number' => 0,
        ]);
        if (!empty($existing)) {
            foreach ($existing as $c) {
                $key = get_comment_meta($c->comment_ID, '_wpdapp_hive_comment_key', true);
                if (!empty($key)) {
                    $existing_index[strtolower($key)] = intval($c->comment_ID);
                }
            }
        }

        foreach ($replies as $reply) {
            $key = strtolower($reply['author'] . '/' . $reply['permlink']);
            if (isset($existing_index[$key])) {
                $skipped++;
                $map_hive_to_wp[$key] = $existing_index[$key];
                continue;
            }

            // Determine parent comment ID if any
            $parent_id = 0;
            $parent_key = '';
            if (!empty($reply['parent_author']) && !empty($reply['parent_permlink'])) {
                $parent_key = strtolower($reply['parent_author'] . '/' . $reply['parent_permlink']);
                if (isset($existing_index[$parent_key])) {
                    $parent_id = $existing_index[$parent_key];
                } elseif (isset($map_hive_to_wp[$parent_key])) {
                    $parent_id = $map_hive_to_wp[$parent_key];
                }
            }

            // Prepare comment data
            $comment_content = isset($reply['body']) ? wp_kses_post($reply['body']) : '';
            $comment_date_gmt = isset($reply['created']) ? gmdate('Y-m-d H:i:s', strtotime($reply['created'])) : gmdate('Y-m-d H:i:s');

            $comment_data = [
                'comment_post_ID' => $post_id,
                'comment_author' => $reply['author'],
                'comment_author_email' => '',
                'comment_author_url' => 'https://hive.blog/@' . $reply['author'],
                'comment_content' => $comment_content,
                'comment_type' => '',
                'comment_parent' => $parent_id,
                'user_id' => 0,
                'comment_date_gmt' => $comment_date_gmt,
                'comment_approved' => $auto_approve ? 1 : 0,
            ];

            $new_id = wp_insert_comment($comment_data);
            if (is_wp_error($new_id) || !$new_id) {
                $skipped++;
                continue;
            }

            // Store mapping meta
            add_comment_meta($new_id, '_wpdapp_hive_comment_key', $key, true);
            if (!empty($parent_key)) {
                add_comment_meta($new_id, '_wpdapp_hive_parent_key', $parent_key, true);
            }

            $imported++;
            $map_hive_to_wp[$key] = intval($new_id);
        }

        wp_send_json_success([
            'imported' => $imported,
            'skipped' => $skipped,
            'total_hive' => is_array($replies) ? count($replies) : 0,
        ]);
    }

    // Legacy endpoint removed.
} 