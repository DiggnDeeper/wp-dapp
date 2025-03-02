<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WP_Dapp_Publish_Handler {

    protected $hive_api;

    public function __construct() {
        // Initialize the Hive API wrapper, which uses the credentials from your settings page.
        $this->hive_api = new WP_Dapp_Hive_API();
    }

    /**
     * Handle the WordPress publish post event.
     *
     * @param int     $post_id The Post ID.
     * @param WP_Post $post    The Post object.
     */
    public function on_publish_post( $post_id, $post ) {
        // Only handle posts, not pages or other post types
        if ( $post->post_type !== 'post' ) {
            return;
        }

        // Check if already published to Hive
        if ( get_post_meta( $post_id, '_hive_published', true ) ) {
            return;
        }
        
        // Check if the user opted out of Hive publishing for this post
        $publish_to_hive = get_post_meta($post_id, '_wpdapp_publish_to_hive', true);
        if ($publish_to_hive === '0') {
            return;
        }

        // Get post categories and tags
        $categories = wp_get_post_categories( $post_id, ['fields' => 'names'] );
        $tags = wp_get_post_tags( $post_id, ['fields' => 'names'] );
        $all_tags = array_merge( $categories, $tags );
        
        // Get custom tags if any
        $custom_tags_string = get_post_meta($post_id, '_wpdapp_custom_tags', true);
        if (!empty($custom_tags_string)) {
            $custom_tags = array_map('trim', explode(',', $custom_tags_string));
            $all_tags = array_merge($all_tags, $custom_tags);
        }
        
        // Get plugin options
        $options = get_option('wpdapp_options');
        
        // Add default tags if enabled
        if (!empty($options['enable_custom_tags']) && !empty($options['default_tags'])) {
            $default_tags = array_map('trim', explode(',', $options['default_tags']));
            $all_tags = array_merge($all_tags, $default_tags);
        }
        
        // Make sure tags are unique and limited to 5
        $all_tags = array_unique($all_tags);
        $all_tags = array_slice($all_tags, 0, 5);
        
        // Get post beneficiaries
        $beneficiaries = get_post_meta($post_id, '_wpdapp_beneficiaries', true);
        if (empty($beneficiaries)) {
            $beneficiaries = [];
        }

        $post_data = [
            'title' => $post->post_title,
            'body' => $this->format_content_for_hive($post->post_content, $post_id),
            'tags' => $all_tags,
            'beneficiaries' => $beneficiaries
        ];

        $result = $this->hive_api->post_to_hive( $post_data );

        if ( is_wp_error( $result ) ) {
            update_post_meta( $post_id, '_hive_publish_error', $result->get_error_message() );
        } else {
            update_post_meta( $post_id, '_hive_published', true );
            update_post_meta( $post_id, '_hive_permlink', $result['permlink'] );
            update_post_meta( $post_id, '_hive_author', $result['author'] );
            
            // Store beneficiary information
            if (!empty($result['beneficiaries'])) {
                update_post_meta( $post_id, '_hive_beneficiaries', $result['beneficiaries'] );
            }
        }
    }
    
    /**
     * Format post content for Hive.
     * 
     * @param string $content The post content.
     * @param int $post_id The post ID.
     * @return string Formatted content.
     */
    private function format_content_for_hive($content, $post_id) {
        // Strip shortcodes
        $content = strip_shortcodes($content);
        
        // Process images if needed
        $content = $this->process_images($content);
        
        // Add a footer with attribution link
        $permalink = get_permalink($post_id);
        $site_name = get_bloginfo('name');
        
        $footer = "\n\n---\n\n";
        $footer .= "Originally published on [$site_name]($permalink)";
        
        return $content . $footer;
    }
    
    /**
     * Process images in content to ensure they work on Hive.
     * 
     * @param string $content The post content.
     * @return string Content with processed images.
     */
    private function process_images($content) {
        // For now, just return the content as is
        // In the future, could process images to ensure they work well on Hive
        return $content;
    }
}

