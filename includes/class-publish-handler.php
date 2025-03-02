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

        // Get post categories and tags
        $categories = wp_get_post_categories( $post_id, ['fields' => 'names'] );
        $tags = wp_get_post_tags( $post_id, ['fields' => 'names'] );
        $all_tags = array_merge( $categories, $tags );

        $post_data = [
            'title' => $post->post_title,
            'body' => $post->post_content,
            'tags' => array_slice( $all_tags, 0, 5 ) // Hive allows max 5 tags
        ];

        $result = $this->hive_api->post_to_hive( $post_data );

        if ( is_wp_error( $result ) ) {
            update_post_meta( $post_id, '_hive_publish_error', $result->get_error_message() );
        } else {
            update_post_meta( $post_id, '_hive_published', true );
            update_post_meta( $post_id, '_hive_permlink', $result['permlink'] );
            update_post_meta( $post_id, '_hive_author', $result['author'] );
        }
    }
}

