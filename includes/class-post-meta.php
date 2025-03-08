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
        add_action('admin_enqueue_scripts', [$this, 'enqueue_editor_scripts']);
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
        // Get Hive publishing status
        $hive_published = get_post_meta($post->ID, '_wpdapp_hive_published', true);
        $hive_author = get_post_meta($post->ID, '_wpdapp_hive_author', true);
        $hive_permlink = get_post_meta($post->ID, '_wpdapp_hive_permlink', true);
        $hive_error = get_post_meta($post->ID, '_wpdapp_hive_error', true);
        
        // Get options
        $options = get_option('wpdapp_options', []);
        $hive_account = isset($options['hive_account']) ? $options['hive_account'] : '';
        
        if (empty($hive_account)) {
            ?>
            <div class="wpdapp-notice-error">
                <p>
                    <strong>Hive account not configured</strong><br>
                    Please configure your Hive account in the 
                    <a href="<?php echo admin_url('options-general.php?page=wpdapp-settings'); ?>">WP-Dapp Settings</a> 
                    before publishing to Hive.
                </p>
            </div>
            <?php
            return;
        }
        
        // Show publishing form if not published, or status if published
        if (!$hive_published) {
            if (!empty($hive_error)) {
                ?>
                <div class="wpdapp-notice-error">
                    <p><strong>Publishing Error:</strong> <?php echo esc_html($hive_error); ?></p>
                </div>
                <?php
            }
            ?>
            <div id="wpdapp-hive-publish">
                <div id="wpdapp-keychain-status"></div>
                
                <div class="wpdapp-publishing-options">
                    <h4>Beneficiaries</h4>
                    <p class="description">Users who will receive a share of this post's rewards.</p>
                    
                    <div class="wpdapp-beneficiaries">
                        <table class="wpdapp-beneficiaries-table">
                            <thead>
                                <tr>
                                    <th>Account</th>
                                    <th>Weight (%)</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $beneficiaries = get_post_meta($post->ID, '_wpdapp_beneficiaries', true);
                                if (!is_array($beneficiaries)) {
                                    $beneficiaries = [];
                                }
                                
                                if (empty($beneficiaries)) {
                                    echo '<tr id="wpdapp-no-beneficiaries"><td colspan="3">No beneficiaries added</td></tr>';
                                } else {
                                    foreach ($beneficiaries as $index => $beneficiary) {
                                        $row_id = 'beneficiary-row-' . esc_attr($index);
                                        ?>
                                        <tr id="<?php echo $row_id; ?>">
                                            <td>
                                                <input type="text" 
                                                       name="wpdapp_beneficiaries[<?php echo $index; ?>][account]" 
                                                       value="<?php echo esc_attr($beneficiary['account']); ?>" />
                                            </td>
                                            <td>
                                                <input type="number" 
                                                       name="wpdapp_beneficiaries[<?php echo $index; ?>][weight]" 
                                                       value="<?php echo esc_attr($beneficiary['weight'] / 100); ?>" 
                                                       min="0.01" max="100" step="0.01" />
                                            </td>
                                            <td>
                                                <button type="button" class="button wpdapp-remove-beneficiary" 
                                                        data-row-id="<?php echo $row_id; ?>">Remove</button>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                        
                        <button type="button" class="button wpdapp-add-beneficiary">Add Beneficiary</button>
                        
                        <!-- Template for adding new beneficiaries (hidden) -->
                        <div id="beneficiary-template" class="beneficiary-template" style="display:none;">
                            <tr id="beneficiary-row-INDEX">
                                <td>
                                    <input type="text" 
                                           name="wpdapp_beneficiaries[INDEX][account]" 
                                           value="" />
                                </td>
                                <td>
                                    <input type="number" 
                                           name="wpdapp_beneficiaries[INDEX][weight]" 
                                           value="10" 
                                           min="0.01" max="100" step="0.01" />
                                </td>
                                <td>
                                    <button type="button" class="button wpdapp-remove-beneficiary"
                                            data-row-id="beneficiary-row-INDEX">Remove</button>
                                </td>
                            </tr>
                        </div>
                    </div>
                </div>
                
                <div class="wpdapp-publish-actions" style="margin-top: 15px;">
                    <button type="button" id="wpdapp-publish-button" class="button button-primary">
                        Publish to Hive with Keychain
                    </button>
                    <div id="wpdapp-publish-status"></div>
                </div>
                
                <?php wp_nonce_field('wpdapp_post_meta', 'wpdapp_nonce'); ?>
            </div>
            
            <script>
                // Add this inline script to localize data for the Keychain publish script
                var wpdapp_publish = {
                    ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    nonce: '<?php echo wp_create_nonce('wpdapp_publish'); ?>',
                    post_id: <?php echo $post->ID; ?>,
                    hive_account: '<?php echo esc_js($hive_account); ?>'
                };
            </script>
            <?php
        } else {
            // Show published status
            ?>
            <div class="wpdapp-published-info">
                <p>
                    <strong class="wpdapp-status-ok">✓ Published to Hive</strong>
                </p>
                
                <p>
                    <strong>Author:</strong> <?php echo esc_html($hive_author); ?><br>
                    <strong>Link:</strong> 
                    <a href="https://hive.blog/@<?php echo esc_attr($hive_author); ?>/<?php echo esc_attr($hive_permlink); ?>" target="_blank">
                        View on Hive
                    </a>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Enqueue scripts and styles for the post editor.
     */
    public function enqueue_editor_scripts($hook) {
        global $post;
        
        if (!$post || ($hook != 'post.php' && $hook != 'post-new.php')) {
            return;
        }
        
        // Enqueue Keychain script
        wp_enqueue_script(
            'hive-keychain',
            'https://hive-keychain.github.io/keychain-sdk/dist/keychain.js',
            [],
            '1.0.0',
            false
        );
        
        // Enqueue Keychain publish script
        wp_enqueue_script(
            'wpdapp-keychain-publish',
            WPDAPP_PLUGIN_URL . 'assets/js/keychain-publish.js',
            ['jquery', 'hive-keychain'],
            WPDAPP_VERSION,
            true
        );
        
        // Enqueue admin styles
        wp_enqueue_style(
            'wpdapp-admin-styles',
            WPDAPP_PLUGIN_URL . 'assets/css/admin-styles.css',
            [],
            WPDAPP_VERSION
        );
    }

    /**
     * Save the meta box data.
     */
    public function save_meta_box_data($post_id) {
        // Check if our nonce is set and verify it
        if (!isset($_POST['wpdapp_nonce']) || !wp_verify_nonce($_POST['wpdapp_nonce'], 'wpdapp_post_meta')) {
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