<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Handles the post meta box for Hive-specific settings.
 */
class WP_Dapp_Post_Meta {

    /**
     * Initialize the class and set up hooks.
     */
    public function __construct() {
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_box_data']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    /**
     * Register the meta box.
     */
    public function add_meta_boxes() {
        add_meta_box(
            'wpdapp_hive_settings',
            'Hive Publishing Settings',
            [$this, 'render_meta_box'],
            'post',
            'side',
            'default'
        );
    }

    /**
     * Enqueue necessary scripts and styles.
     */
    public function enqueue_scripts($hook) {
        // Only on post edit screen
        if (!in_array($hook, ['post.php', 'post-new.php'])) {
            return;
        }

        wp_enqueue_script(
            'wpdapp-admin-script',
            WPDAPP_PLUGIN_URL . 'assets/js/admin-script.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_enqueue_style(
            'wpdapp-admin-style',
            WPDAPP_PLUGIN_URL . 'assets/css/admin-style.css',
            [],
            '1.0.0'
        );
    }

    /**
     * Render the meta box.
     */
    public function render_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('wpdapp_save_meta_box_data', 'wpdapp_meta_box_nonce');

        // Get saved values
        $publish_to_hive = get_post_meta($post->ID, '_wpdapp_publish_to_hive', true);
        $custom_tags = get_post_meta($post->ID, '_wpdapp_custom_tags', true);
        $beneficiaries = get_post_meta($post->ID, '_wpdapp_beneficiaries', true);

        // Default values
        if (empty($publish_to_hive)) {
            $publish_to_hive = '1'; // Default to yes
        }

        ?>
        <div class="wpdapp-meta-box">
            <p>
                <label for="wpdapp_publish_to_hive">
                    <input type="checkbox" name="wpdapp_publish_to_hive" id="wpdapp_publish_to_hive" value="1" <?php checked('1', $publish_to_hive); ?>>
                    Publish to Hive when post is published
                </label>
            </p>

            <p>
                <label for="wpdapp_custom_tags">Custom Tags:</label>
                <input type="text" name="wpdapp_custom_tags" id="wpdapp_custom_tags" value="<?php echo esc_attr($custom_tags); ?>" class="widefat">
                <span class="description">Comma-separated. These will be used in addition to WordPress categories and tags.</span>
            </p>

            <div class="wpdapp-beneficiaries">
                <p><strong>Beneficiaries:</strong> <a href="#" class="add-beneficiary button button-small">Add</a></p>
                
                <div class="beneficiary-list">
                    <?php
                    if (!empty($beneficiaries) && is_array($beneficiaries)) {
                        foreach ($beneficiaries as $index => $beneficiary) {
                            $account = isset($beneficiary['account']) ? $beneficiary['account'] : '';
                            $weight = isset($beneficiary['weight']) ? $beneficiary['weight'] / 100 : ''; // Convert to percentage
                            ?>
                            <div class="beneficiary-item">
                                <input type="text" name="wpdapp_beneficiaries[<?php echo $index; ?>][account]" 
                                       placeholder="Hive account" value="<?php echo esc_attr($account); ?>" class="widefat">
                                <input type="number" name="wpdapp_beneficiaries[<?php echo $index; ?>][weight]" 
                                       placeholder="%" min="1" max="100" value="<?php echo esc_attr($weight); ?>" class="small-text">
                                <a href="#" class="remove-beneficiary dashicons dashicons-no-alt"></a>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
                
                <div class="beneficiary-template" style="display:none;">
                    <div class="beneficiary-item">
                        <input type="text" name="wpdapp_beneficiaries[INDEX][account]" placeholder="Hive account" class="widefat">
                        <input type="number" name="wpdapp_beneficiaries[INDEX][weight]" placeholder="%" min="1" max="100" class="small-text">
                        <a href="#" class="remove-beneficiary dashicons dashicons-no-alt"></a>
                    </div>
                </div>
            </div>

            <?php
            // Display Hive publication status if already published
            $hive_published = get_post_meta($post->ID, '_hive_published', true);
            $hive_permlink = get_post_meta($post->ID, '_hive_permlink', true);
            $hive_author = get_post_meta($post->ID, '_hive_author', true);
            $hive_publish_error = get_post_meta($post->ID, '_hive_publish_error', true);

            if ($hive_published && $hive_permlink && $hive_author) {
                echo '<div class="hive-status success">';
                echo '<p><strong>Hive Status:</strong> Published</p>';
                echo '<p><a href="https://hive.blog/@' . esc_attr($hive_author) . '/' . esc_attr($hive_permlink) . '" target="_blank">';
                echo 'View on Hive <span class="dashicons dashicons-external"></span></a></p>';
                echo '</div>';
            } elseif ($hive_publish_error) {
                echo '<div class="hive-status error">';
                echo '<p><strong>Hive Status:</strong> Error</p>';
                echo '<p>' . esc_html($hive_publish_error) . '</p>';
                echo '</div>';
            }
            ?>
        </div>
        <?php
    }

    /**
     * Save the meta box data.
     */
    public function save_meta_box_data($post_id) {
        // Check if our nonce is set and verify it
        if (!isset($_POST['wpdapp_meta_box_nonce']) || !wp_verify_nonce($_POST['wpdapp_meta_box_nonce'], 'wpdapp_save_meta_box_data')) {
            return;
        }

        // If this is an autosave, we don't want to do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check the user's permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save the publish to Hive option
        $publish_to_hive = isset($_POST['wpdapp_publish_to_hive']) ? '1' : '0';
        update_post_meta($post_id, '_wpdapp_publish_to_hive', $publish_to_hive);

        // Save custom tags
        if (isset($_POST['wpdapp_custom_tags'])) {
            update_post_meta($post_id, '_wpdapp_custom_tags', sanitize_text_field($_POST['wpdapp_custom_tags']));
        }

        // Save beneficiaries
        $beneficiaries = [];
        if (isset($_POST['wpdapp_beneficiaries']) && is_array($_POST['wpdapp_beneficiaries'])) {
            foreach ($_POST['wpdapp_beneficiaries'] as $beneficiary) {
                if (!empty($beneficiary['account'])) {
                    $account = sanitize_text_field($beneficiary['account']);
                    $weight = isset($beneficiary['weight']) ? intval($beneficiary['weight']) : 0;
                    
                    // Store weight as internal value (0-10000)
                    $weight = min(10000, max(1, $weight * 100));
                    
                    $beneficiaries[] = [
                        'account' => $account,
                        'weight' => $weight
                    ];
                }
            }
        }
        
        update_post_meta($post_id, '_wpdapp_beneficiaries', $beneficiaries);
    }
} 