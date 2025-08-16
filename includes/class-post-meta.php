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
            'Publish to Hive',
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

        wp_enqueue_style('dashicons');

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
        // Auto-publish feature removed; no ready flag is used.
        
        // Get options
        $options = get_option('wpdapp_options', []);
        $hive_account = isset($options['hive_account']) ? $options['hive_account'] : '';
        
        // Add direct vanilla JavaScript for removing beneficiaries
        ?>
        <script type="text/javascript">
            function wpdappRemoveBeneficiary(button) {
                // Find the closest parent with class wpdapp-beneficiary-row
                var row = button;
                while (row && !row.classList.contains('wpdapp-beneficiary-row')) {
                    row = row.parentNode;
                }
                
                // Remove the row if found
                if (row && row.parentNode) {
                    row.parentNode.removeChild(row);
                    console.log('Beneficiary row removed successfully');
                } else {
                    console.error('Could not find beneficiary row to remove');
                }
                return false;
            }
        </script>
        <?php
        
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
        
        // Show error message if auto-publish failed
        if (!$hive_published && !empty($hive_error)) {
            ?>
            <div class="wpdapp-notice-error">
                <p>
                    <strong>Error:</strong> <?php echo esc_html($hive_error); ?>
                </p>
            </div>
            <?php
        }
        
        // No auto-publish ready notice.
        
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
                <!-- Keychain Status -->
                <div class="wpdapp-keychain-status">
                    <?php 
                    // Check if Keychain is available (via JS)
                    // We just show the placeholder, it'll be updated via JS
                    ?>
                    <p id="wpdapp-keychain-detection">
                        <span class="wpdapp-status-checking">
                            <span class="dashicons dashicons-update"></span> Checking for Hive Keychain...
                        </span>
                    </p>
                </div>
                
                <!-- Beneficiaries Section -->
                <h4><span class="dashicons dashicons-groups"></span> Beneficiaries</h4>
                <p class="description">Users who will receive a share of rewards.</p>
                
                <div class="wpdapp-beneficiaries">
                    <?php
                    // Get existing beneficiaries
                    $beneficiaries = get_post_meta($post->ID, '_wpdapp_beneficiaries', true);
                    if (!is_array($beneficiaries)) {
                        $beneficiaries = [];
                    }
                    
                    // Add a dummy empty entry if none exist to show at least one row
                    if (empty($beneficiaries)) {
                        $beneficiaries = [['account' => '', 'weight' => 1000]]; // 10%
                    }
                    ?>
                    
                    <div id="wpdapp-beneficiaries-container">
                        <?php foreach ($beneficiaries as $index => $beneficiary): ?>
                            <div class="wpdapp-beneficiary-row">
                                <div class="wpdapp-beneficiary-inputs">
                                    <input type="text" 
                                           name="wpdapp_beneficiaries[<?php echo $index; ?>][account]" 
                                           placeholder="Username"
                                           value="<?php echo esc_attr($beneficiary['account']); ?>" />
                                    
                                    <input type="number" 
                                           name="wpdapp_beneficiaries[<?php echo $index; ?>][weight]" 
                                           placeholder="%"
                                           value="<?php echo esc_attr($beneficiary['weight'] / 100); ?>" 
                                           min="0.01" max="100" step="0.01" />
                                </div>
                                
                                <button type="button" class="button wpdapp-remove-beneficiary" 
                                        onclick="wpdappRemoveBeneficiary(this); return false;"
                                        title="Remove beneficiary">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="button" id="wpdapp-add-beneficiary" class="button" title="Add new beneficiary">
                        <span class="dashicons dashicons-plus-alt"></span> Add
                    </button>
                </div>
                
                <!-- Publish Button -->
                <div class="wpdapp-publish-actions">
                    <button type="button" id="wpdapp-publish-button" class="button button-primary">
                        <span class="dashicons dashicons-share-alt2"></span> Publish to Hive
                    </button>
                    <button type="button" id="wpdapp-sync-comments-button" class="button">
                        <span class="dashicons dashicons-update"></span> Sync Comments
                    </button>
                    <div id="wpdapp-publish-status"></div>
                    <div id="wpdapp-sync-status"></div>
                </div>
                
                <?php wp_nonce_field('wpdapp_post_meta', 'wpdapp_nonce'); ?>
                
                <script>
                    // Add this inline script to localize data for the Keychain publish script
                    var wpdapp_publish = {
                        ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        nonce: '<?php echo wp_create_nonce('wpdapp_publish'); ?>',
                        post_id: <?php echo $post->ID; ?>,
                        hive_account: '<?php echo esc_js($hive_account); ?>'
                    };
                    
                    // Simple Keychain detection
                    jQuery(document).ready(function($) {
                        setTimeout(function() {
                            var keychainDetected = typeof window.hive_keychain !== 'undefined';
                            var statusElem = $('#wpdapp-keychain-detection');
                            
                            if (keychainDetected) {
                                statusElem.html('<span class="wpdapp-status-ok"><span class="dashicons dashicons-yes"></span> Hive Keychain detected</span>');
                            } else {
                                statusElem.html('<span class="wpdapp-status-error"><span class="dashicons dashicons-no"></span> Hive Keychain not detected</span><br><small>Please <a href="https://hive-keychain.com/" target="_blank">install Hive Keychain</a> to publish to Hive.</small>');
                            }
                        }, 500); // Short delay to ensure Keychain is loaded
                    });
                </script>
            </div>
            <?php
        } else {
            // Show published status + sync UI
            ?>
            <div class="wpdapp-published-info">
                <p>
                    <strong class="wpdapp-status-ok"><span class="dashicons dashicons-yes"></span> Published to Hive</strong>
                </p>
                
                <p>
                    <strong>Author:</strong> <?php echo esc_html($hive_author); ?><br>
                    <strong>Link:</strong> 
                    <a href="https://hive.blog/@<?php echo esc_attr($hive_author); ?>/<?php echo esc_attr($hive_permlink); ?>" target="_blank">
                        View on Hive <span class="dashicons dashicons-external"></span>
                    </a>
                </p>
            </div>

            <div class="wpdapp-publish-actions">
                <button type="button" id="wpdapp-sync-comments-button" class="button">
                    <span class="dashicons dashicons-update"></span> Sync Comments
                </button>
                <div id="wpdapp-sync-status"></div>
            </div>

            <script>
                // Ensure the data object exists for comment sync
                var wpdapp_publish = {
                    ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    nonce: '<?php echo wp_create_nonce('wpdapp_publish'); ?>',
                    post_id: <?php echo $post->ID; ?>,
                    hive_account: '<?php echo esc_js($hive_account); ?>'
                };
            </script>
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