<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WP_Dapp_Publish_Handler {

    protected $hive_api;

    public function __construct() {
        // Initialize the Hive API wrapper
        $this->hive_api = new WP_Dapp_Hive_API();
        
        // Register hooks for post meta box - DISABLED to prevent duplicate meta boxes
        // add_action('add_meta_boxes', [$this, 'add_hive_publish_meta_box']);
        
        // Add hook for auto-publishing to Hive when a post is published
        add_action('transition_post_status', [$this, 'handle_post_status_transition'], 10, 3);
    }

    /**
     * Handle post status transitions for auto-publishing
     * 
     * @param string $new_status The new post status
     * @param string $old_status The old post status
     * @param WP_Post $post The post object
     */
    public function handle_post_status_transition($new_status, $old_status, $post) {
        // Only proceed if:
        // 1. The post is transitioning to 'publish'
        // 2. It wasn't already published (to prevent re-publishing)
        // 3. It's a regular post (not a page or custom post type)
        if ($new_status !== 'publish' || $old_status === 'publish' || $post->post_type !== 'post') {
            return;
        }
        
        // Check if this post is already published to Hive
        $hive_published = get_post_meta($post->ID, '_wpdapp_hive_published', true);
        if ($hive_published) {
            return;
        }
        
        // Get plugin options
        $options = get_option('wpdapp_options', []);
        
        // Check if auto-publish is enabled
        if (empty($options['auto_publish'])) {
            return;
        }
        
        // Check if Hive account is configured
        if (empty($options['hive_account'])) {
            // Log error or notify admin that auto-publish is enabled but Hive account is not configured
            update_post_meta($post->ID, '_wpdapp_hive_error', 'Auto-publish failed: Hive account not configured');
            return;
        }
        
        // Note: We can't do the actual publishing here since it requires Keychain interaction
        // Instead, we'll add a notification to alert the user
        
        // Add a flag indicating this post is ready for Hive publishing
        update_post_meta($post->ID, '_wpdapp_auto_publish_ready', 1);
        
        // Add an admin notice for this post
        add_action('admin_notices', function() use ($post) {
            // Only show on the post edit screen for this specific post
            $screen = get_current_screen();
            if (!$screen || $screen->base !== 'post' || $screen->id !== 'post' || 
                !isset($_GET['post']) || intval($_GET['post']) !== $post->ID) {
                return;
            }
            
            ?>
            <div class="notice notice-info is-dismissible">
                <p>
                    <strong>WP-Dapp:</strong> 
                    This post is ready to be published to Hive. 
                    <a href="#wpdapp_hive_settings">Click here</a> to publish it manually using Hive Keychain.
                </p>
            </div>
            <?php
        });
    }

    /**
     * Add Hive publish meta box to post editor
     * DISABLED: This functionality is now handled by the WP_Dapp_Post_Meta class
     */
    public function add_hive_publish_meta_box() {
        // Disabled to prevent duplicate meta boxes
        /*
        add_meta_box(
            'wpdapp_hive_publish_box',
            'Publish to Hive',
            [$this, 'render_hive_publish_meta_box'],
            'post',
            'side',
            'default'
        );
        */
    }
    
    /**
     * Render the Hive publish meta box
     *
     * @param WP_Post $post The post object
     */
    public function render_hive_publish_meta_box($post) {
        // Get Hive account
        $options = get_option('wpdapp_options', []);
        $hive_account = !empty($options['hive_account']) ? $options['hive_account'] : '';
        
        // Check if post is published to Hive
        $hive_published = get_post_meta($post->ID, '_wpdapp_hive_published', true);
        $hive_permlink = get_post_meta($post->ID, '_wpdapp_hive_permlink', true);
        
        if ($hive_published && $hive_permlink) {
            // Show published status
            echo '<div class="wpdapp-published-info">';
            echo '<p><strong style="color: green;">✓ Published to Hive</strong></p>';
            echo '<p><a href="https://hive.blog/@' . esc_attr($hive_account) . '/' . esc_attr($hive_permlink) . '" target="_blank">View on Hive</a></p>';
            echo '</div>';
            return;
        }
        
        // If Hive account is not set, show warning
        if (empty($hive_account)) {
            echo '<p style="color: red;">Hive account not configured. Please configure it in the <a href="' . admin_url('options-general.php?page=wpdapp-settings') . '">WP-Dapp Settings</a>.</p>';
            return;
        }
        
        // Show publish button only for published posts
        if ($post->post_status !== 'publish') {
            echo '<p>Publish this post in WordPress first before publishing to Hive.</p>';
            return;
        }
        
        // Show Keychain publish button
        echo '<div id="wpdapp-keychain-status"></div>';
        echo '<button type="button" id="wpdapp-publish-button" class="button button-primary">Publish to Hive with Keychain</button>';
        echo '<div id="wpdapp-publish-status"></div>';
        
        // Add nonce and meta data for JavaScript
        wp_nonce_field('wpdapp_publish', 'wpdapp_publish_nonce');
        
        // Add inline script with data for the Keychain publish script
        echo '<script>';
        echo 'var wpdapp_publish = {';
        echo 'ajax_url: "' . admin_url('admin-ajax.php') . '",';
        echo 'nonce: "' . wp_create_nonce('wpdapp_publish') . '",';
        echo 'post_id: ' . $post->ID . ',';
        echo 'hive_account: "' . esc_js($hive_account) . '"';
        echo '};';
        echo '</script>';
    }
    
    /**
     * Format post content for Hive.
     * 
     * @param string $content The post content.
     * @param int $post_id The post ID.
     * @return string Formatted content.
     */
    public function format_content_for_hive($content, $post_id) {
        // Strip shortcodes
        $content = strip_shortcodes($content);
        
        // Strip Gutenberg block comments
        // Original regex was limited - this improved version handles all variations:
        // - Works with or without whitespace
        // - Handles multiline comments
        // - Catches both opening and closing tags in one pass
        // - Uses the s (PCRE_DOTALL) modifier to make dot match newlines
        $content = preg_replace('/<!--\s*wp:.*?(?:-->|\/-->)/s', '', $content); // Opening tags
        $content = preg_replace('/<!--\s*\/wp:.*?(?:-->|\/-->)/s', '', $content); // Closing tags
        
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

