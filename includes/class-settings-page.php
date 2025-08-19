<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WP_Dapp_Settings_Page {

    private $options;
    private $hive_api;

    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        $this->hive_api = new WP_Dapp_Hive_API();
    }

    /**
     * Add options page under the Settings menu.
     */
    public function add_settings_page() {
        add_options_page(
            __('WP-Dapp Settings', 'wp-dapp'),
            __('WP-Dapp', 'wp-dapp'),
            'manage_options',
            'wpdapp-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_scripts($hook) {
        // Only load on our settings page
        if ($hook != 'settings_page_wpdapp-settings') {
            return;
        }

        // Enqueue the Keychain API script from CDN
        wp_enqueue_script(
            'hive-keychain',
            'https://hive-keychain.github.io/keychain-sdk/dist/keychain.js',
            [],
            '1.0.0',
            false
        );

        // Enqueue our admin settings scripts
        wp_enqueue_script(
            'wpdapp-keychain-integration',
            WPDAPP_PLUGIN_URL . 'assets/js/keychain-integration.js',
            ['jquery', 'hive-keychain'],
            WPDAPP_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script(
            'wpdapp-keychain-integration',
            'wpdapp_settings',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpdapp_verify_credentials'),
                'verify_text' => __('Verify with Keychain', 'wp-dapp'),
                'verifying_text' => __('Verifying...', 'wp-dapp'),
                'success_text' => __('Account verified successfully!', 'wp-dapp'),
                'error_text' => __('Verification failed', 'wp-dapp')
            ]
        );
        
        // Enqueue our admin CSS
        wp_enqueue_style(
            'wpdapp-admin-styles',
            WPDAPP_PLUGIN_URL . 'assets/css/admin-styles.css',
            [],
            WPDAPP_VERSION
        );
        
        // Intentionally do not force auto_publish here; default is handled on install/upgrade.
    }

    /**
     * Register and add settings.
     */
    public function register_settings() {
        register_setting('wpdapp_options_group', 'wpdapp_options', [
            'sanitize_callback' => [$this, 'sanitize_options']
        ]);
        
        // Account Settings Section
        add_settings_section(
            'wpdapp_account_section',
            'Hive Account Settings',
            [$this, 'account_section_callback'],
            'wpdapp-settings'
        );
        
        add_settings_field(
            'hive_account',
            __('Hive Account', 'wp-dapp'),
            [$this, 'render_field'],
            'wpdapp-settings',
            'wpdapp_account_section',
            ['field' => 'hive_account']
        );
        
        add_settings_field(
            'keychain_status',
            'Keychain Status',
            [$this, 'render_keychain_status'],
            'wpdapp-settings',
            'wpdapp_account_section'
        );
        
        add_settings_field(
            'verify_account',
            'Verify Account',
            [$this, 'render_verify_button'],
            'wpdapp-settings',
            'wpdapp_account_section'
        );
        
        // Beneficiary Settings Section
        add_settings_section(
            'wpdapp_beneficiary_section',
            'Beneficiary Settings',
            [$this, 'beneficiary_section_callback'],
            'wpdapp-settings'
        );
        
        add_settings_field(
            'enable_default_beneficiary',
            __('Enable Default Beneficiary', 'wp-dapp'),
            [$this, 'render_field'],
            'wpdapp-settings',
            'wpdapp_beneficiary_section',
            ['field' => 'enable_default_beneficiary', 'type' => 'checkbox']
        );
        
        add_settings_field(
            'default_beneficiary_account',
            __('Default Beneficiary Account', 'wp-dapp'),
            [$this, 'render_field'],
            'wpdapp-settings',
            'wpdapp_beneficiary_section',
            ['field' => 'default_beneficiary_account']
        );
        
        add_settings_field(
            'default_beneficiary_weight',
            __('Default Beneficiary Percentage', 'wp-dapp'),
            [$this, 'render_field'],
            'wpdapp-settings',
            'wpdapp_beneficiary_section',
            [
                'field' => 'default_beneficiary_weight', 
                'type' => 'number',
                'min' => 0.1,
                'max' => 10,
                'step' => 0.1,
                'description' => __('Percentage of rewards (0.1-10%). Default is 1%.', 'wp-dapp'),
                'class' => 'wpdapp-percentage-field'
            ]
        );
        
        // Post Settings Section
        add_settings_section(
            'wpdapp_post_section',
            'Post Settings',
            [$this, 'post_section_callback'],
            'wpdapp-settings'
        );
        
        add_settings_field(
            'default_tags',
            __('Default Tags', 'wp-dapp'),
            [$this, 'render_field'],
            'wpdapp-settings',
            'wpdapp_post_section',
            [
                'field' => 'default_tags',
                'description' => __('Comma-separated list of default tags to use when none are specified.', 'wp-dapp')
            ]
        );

        // Comment Sync Section
        add_settings_section(
            'wpdapp_comment_sync_section',
            'Comment Sync',
            [$this, 'comment_sync_section_callback'],
            'wpdapp-settings'
        );

        add_settings_field(
            'enable_comment_sync',
            __('Enable Comment Sync', 'wp-dapp'),
            [$this, 'render_field'],
            'wpdapp-settings',
            'wpdapp_comment_sync_section',
            ['field' => 'enable_comment_sync', 'type' => 'checkbox', 'label' => __('Import Hive replies into WordPress comments', 'wp-dapp')]
        );

        add_settings_field(
            'auto_approve_comments',
            __('Auto‑approve Imported Comments', 'wp-dapp'),
            [$this, 'render_field'],
            'wpdapp-settings',
            'wpdapp_comment_sync_section',
            [
                'field' => 'auto_approve_comments',
                'type' => 'checkbox',
                'label' => __('Mark imported comments as approved', 'wp-dapp'),
                'description' => __('Applies to the native WP comments display. In Hive‑only display, all imported comments are shown.', 'wp-dapp')
            ]
        );

        add_settings_field(
            'hive_only_mode',
            __('Hive‑only Display', 'wp-dapp'),
            [$this, 'render_field'],
            'wpdapp-settings',
            'wpdapp_comment_sync_section',
            [
                'field' => 'hive_only_mode',
                'type' => 'checkbox',
                'label' => __('Hide the WP comment form and always show mirrored Hive replies with a “Reply on Hive” link', 'wp-dapp'),
                'description' => __('Imported comments display regardless of approval status.', 'wp-dapp')
            ]
        );

        add_settings_field(
            'show_reply_buttons',
            __('Show Reply Buttons', 'wp-dapp'),
            [$this, 'render_field'],
            'wpdapp-settings',
            'wpdapp_comment_sync_section',
            [
                'field' => 'show_reply_buttons',
                'type' => 'checkbox',
                'label' => __('Enable Keychain reply buttons on-site', 'wp-dapp'),
                'description' => __('Adds “Reply with Keychain” buttons above the thread and on each imported comment. Requires the Hive Keychain browser extension. Buttons are visible in both display modes; posting from them still requires comment sync to be enabled to mirror replies back into WP.', 'wp-dapp')
            ]
        );
        
        add_settings_field(
            'hive_frontend',
            __('Hive Frontend', 'wp-dapp'),
            [$this, 'render_field'],
            'wpdapp-settings',
            'wpdapp_comment_sync_section',
            [
                'field' => 'hive_frontend',
                'type' => 'select',
                'options' => [
                    'peakd' => __('PeakD', 'wp-dapp'),
                    'hive.blog' => __('Hive.blog', 'wp-dapp'),
                    'ecency' => __('Ecency', 'wp-dapp')
                ],
                'description' => __('Choose which Hive frontend to use for links (e.g., View thread).', 'wp-dapp')
            ]
        );

        add_settings_field(
            'hive_max_thread_depth',
            __('Max Thread Depth', 'wp-dapp'),
            [$this, 'render_field'],
            'wpdapp-settings',
            'wpdapp_comment_sync_section',
            [
                'field' => 'hive_max_thread_depth',
                'type' => 'number',
                'min' => 1,
                'max' => 10,
                'step' => 1,
                'description' => __('How many nested levels to render on-site (1–10). Deeper replies remain available via the \"View more replies on Hive\" link.', 'wp-dapp')
            ]
        );
        
        // Advanced Settings Section
        add_settings_section(
            'wpdapp_advanced_section',
            'Advanced Settings',
            [$this, 'advanced_section_callback'],
            'wpdapp-settings'
        );
        
        add_settings_field(
            'hive_api_node',
            __('Hive API Node', 'wp-dapp'),
            [$this, 'render_field'],
            'wpdapp-settings',
            'wpdapp_advanced_section',
            [
                'field' => 'hive_api_node',
                'description' => __('Custom Hive API node URL. Leave blank to use the default (api.hive.blog).', 'wp-dapp')
            ]
        );
    }

    /**
     * Sanitize options before saving.
     */
    public function sanitize_options($options) {
        $sanitized = [];
        
        // Get existing options
        $existing_options = get_option('wpdapp_options', []);
        
        // Account settings
        $sanitized['hive_account'] = sanitize_text_field($options['hive_account']);
        
        // Beneficiary settings
        $sanitized['enable_default_beneficiary'] = isset($options['enable_default_beneficiary']) ? 1 : 0;
        $sanitized['default_beneficiary_account'] = sanitize_text_field($options['default_beneficiary_account']);
        
        // Ensure the beneficiary weight is properly formatted and within valid range
        $weight = isset($options['default_beneficiary_weight']) ? floatval($options['default_beneficiary_weight']) : 1.0;
        $weight = max(0.1, min(10, $weight)); // Ensure value is between 0.1 and 10
        $sanitized['default_beneficiary_weight'] = intval($weight * 100); // Store as integer (1% = 100)
        
        // Publishing settings
        $sanitized['default_tags'] = sanitize_text_field($options['default_tags']);

        // Comment sync settings
        $sanitized['enable_comment_sync'] = isset($options['enable_comment_sync']) ? 1 : 0;
        $sanitized['auto_approve_comments'] = isset($options['auto_approve_comments']) ? 1 : 0;
        $sanitized['hive_only_mode'] = isset($options['hive_only_mode']) ? 1 : 0;
        $sanitized['show_reply_buttons'] = isset($options['show_reply_buttons']) ? 1 : 0;
        // Hive frontend choice
        $allowed_frontends = ['peakd', 'hive.blog', 'ecency'];
        $chosen_frontend = isset($options['hive_frontend']) ? sanitize_text_field($options['hive_frontend']) : '';
        if (!in_array($chosen_frontend, $allowed_frontends, true)) {
            $chosen_frontend = isset($existing_options['hive_frontend']) ? $existing_options['hive_frontend'] : 'peakd';
            if (!in_array($chosen_frontend, $allowed_frontends, true)) {
                $chosen_frontend = 'peakd';
            }
        }
        $sanitized['hive_frontend'] = $chosen_frontend;
        // Max thread depth
        $max_depth = isset($options['hive_max_thread_depth']) ? intval($options['hive_max_thread_depth']) : 4;
        if ($max_depth < 1) { $max_depth = 1; }
        if ($max_depth > 10) { $max_depth = 10; }
        $sanitized['hive_max_thread_depth'] = $max_depth;
        
        // Advanced settings
        $sanitized['hive_api_node'] = sanitize_text_field($options['hive_api_node']);
        
        return $sanitized;
    }

    /**
     * Print the Section text.
     */
    public function account_section_callback() {
        echo '<div class="wpdapp-settings-section-description">';
        echo '<p>' . __('Configure your Hive account settings for publishing to the Hive blockchain.', 'wp-dapp') . '</p>';
        echo '</div>';
    }
    
    /**
     * Print the Beneficiary Section text.
     */
    public function beneficiary_section_callback() {
        echo '<div class="wpdapp-settings-section-description">';
        echo '<p>' . __('Configure beneficiaries for your Hive posts. Beneficiaries receive a percentage of post rewards.', 'wp-dapp') . '</p>';
        echo '<p><em>' . __('Note: A small percentage can be automatically set to support the WP-Dapp plugin development.', 'wp-dapp') . '</em></p>';
        echo '</div>';
    }
    
    /**
     * Print the Post Section text.
     */
    public function post_section_callback() {
        echo '<div class="wpdapp-settings-section-description">';
        echo '<p>' . __('Configure post settings for your Hive posts.', 'wp-dapp') . '</p>';
        echo '</div>';
    }

    /**
     * Print the Comment Sync Section text.
     */
    public function comment_sync_section_callback() {
        echo '<div class="wpdapp-settings-section-description">';
        echo '<p>' . __('Import replies from Hive as WordPress comments, then choose how to display them on-site.', 'wp-dapp') . '</p>';
        echo '<p>' . __('Display modes:', 'wp-dapp') . ' ' . __('(1) Native WP comments — uses your theme’s comments template; auto‑approval affects visibility. (2) Hive‑only — replace the WP comment form and always show the mirrored Hive replies with a link to reply on Hive.', 'wp-dapp') . '</p>';
        echo '<p><strong>' . __('Note:', 'wp-dapp') . '</strong> ' . __('WordPress comments can be globally disabled to avoid spam. Imported Hive replies will still display using the <code>[wpdapp_hive_comments]</code> shortcode and a post footer notice will link users to reply on Hive. If you want imported comments to appear in the native WP comments template, ensure comments are enabled for that post and in Settings → Discussion.', 'wp-dapp') . '</p>';
        echo '</div>';
    }
    
    /**
     * Print the Advanced Section text.
     */
    public function advanced_section_callback() {
        echo '<div class="wpdapp-settings-section-description">';
        echo '<p>' . __('Advanced settings for the WP-Dapp plugin.', 'wp-dapp') . '</p>';
        echo '</div>';
    }

    /**
     * Render the Keychain status.
     */
    public function render_keychain_status() {
        ?>
        <div class="wpdapp-keychain-status">
            <p id="wpdapp-keychain-detection">
                <span class="wpdapp-status-checking">
                    <span class="dashicons dashicons-update"></span> Checking for Hive Keychain...
                </span>
            </p>
        </div>
        <?php
    }

    /**
     * Render the verify button.
     */
    public function render_verify_button() {
        $options = get_option('wpdapp_options');
        $hive_account = isset($options['hive_account']) ? $options['hive_account'] : '';
        
        ?>
        <div class="wpdapp-verify-button-container">
            <button type="button" id="wpdapp-verify-button" class="button" <?php echo empty($hive_account) ? 'disabled' : ''; ?>>
                <span class="dashicons dashicons-yes"></span> Verify with Keychain
            </button>
            <div id="wpdapp-verify-status"></div>
            <p class="description"><?php _e('Click this button to verify your Hive account with Keychain.', 'wp-dapp'); ?></p>
        </div>
        <?php
    }

    /**
     * Render settings field.
     */
    public function render_field($args) {
        $options = get_option('wpdapp_options');
        $field = $args['field'];
        $type = isset($args['type']) ? $args['type'] : 'text';
        $value = isset($options[$field]) ? $options[$field] : '';
        $class = isset($args['class']) ? $args['class'] : '';
        $description = isset($args['description']) ? $args['description'] : '';
        
        echo '<div class="wpdapp-settings-field">';
        
        // Handle different field types
        switch ($type) {
            case 'checkbox':
                // Checkbox handling (including auto_publish)
                printf(
                    '<label for="%s"><input type="checkbox" id="%s" name="wpdapp_options[%s]" value="1" %s> %s</label>',
                    esc_attr($field),
                    esc_attr($field),
                    esc_attr($field),
                    checked(1, $value, false),
                    isset($args['label']) ? esc_html($args['label']) : ''
                );
                break;
            
            case 'select':
                $options_map = isset($args['options']) && is_array($args['options']) ? $args['options'] : [];
                if (empty($value)) {
                    $value = 'peakd';
                }
                printf('<select id="%s" name="wpdapp_options[%s]">', esc_attr($field), esc_attr($field));
                foreach ($options_map as $opt_value => $label) {
                    printf('<option value="%s" %s>%s</option>', esc_attr($opt_value), selected($value, $opt_value, false), esc_html($label));
                }
                echo '</select>';
                break;
                
            case 'number':
                $min = isset($args['min']) ? $args['min'] : '';
                $max = isset($args['max']) ? $args['max'] : '';
                $step = isset($args['step']) ? $args['step'] : '';
                $input_class = !empty($class) ? $class : 'small-text';
                
                // Convert weight from internal storage (0-1000) to percentage (0-10)
                if ($field === 'default_beneficiary_weight' && !empty($value)) {
                    // Handle case where the value is already in the 0-1000 range (stored as integer)
                    if ($value > 10) {
                        $value = number_format($value / 100, 1); // Format to 1 decimal place
                    } else {
                        $value = number_format($value, 1); // Already a percentage, just format
                    }
                }
                
                // For percentage field, add a wrapper with the % symbol
                if ($field === 'default_beneficiary_weight') {
                    echo '<div class="wpdapp-input-with-suffix">';
                    printf(
                        '<input type="number" id="%s" name="wpdapp_options[%s]" value="%s" class="%s" %s %s %s>',
                        esc_attr($field),
                        esc_attr($field),
                        esc_attr($value),
                        esc_attr($input_class),
                        !empty($min) ? "min=\"{$min}\"" : "",
                        !empty($max) ? "max=\"{$max}\"" : "",
                        !empty($step) ? "step=\"{$step}\"" : ""
                    );
                    echo '<span class="wpdapp-input-suffix">%</span>';
                    echo '</div>';
                } else {
                    printf(
                        '<input type="number" id="%s" name="wpdapp_options[%s]" value="%s" class="%s" %s %s %s>',
                        esc_attr($field),
                        esc_attr($field),
                        esc_attr($value),
                        esc_attr($input_class),
                        !empty($min) ? "min=\"{$min}\"" : "",
                        !empty($max) ? "max=\"{$max}\"" : "",
                        !empty($step) ? "step=\"{$step}\"" : ""
                    );
                }
                break;
                
            case 'password':
                printf(
                    '<input type="password" id="%s" name="wpdapp_options[%s]" value="%s" class="regular-text" autocomplete="off">',
                    esc_attr($field),
                    esc_attr($field),
                    esc_attr($value)
                );
                break;
                
            default:
                printf(
                    '<input type="text" id="%s" name="wpdapp_options[%s]" value="%s" class="regular-text">',
                    esc_attr($field),
                    esc_attr($field),
                    esc_attr($value)
                );
                break;
        }
        
        // Add description if provided
        if (!empty($description)) {
            printf('<p class="description">%s</p>', esc_html($description));
        }
        
        echo '</div>';
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page() {
        ?>
        <div class="wrap wpdapp-settings-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="options.php" class="wpdapp-settings-form">
                <?php
                settings_fields('wpdapp_options_group');
                ?>
                
                <div class="wpdapp-settings-container">
                    <?php do_settings_sections('wpdapp-settings'); ?>
                </div>
                
                <?php submit_button('Save Settings'); ?>
            </form>
            
            <div class="wpdapp-settings-footer">
                <h3><?php _e('About WP-Dapp', 'wp-dapp'); ?></h3>
                <p><?php _e('WP-Dapp is a WordPress plugin that enables publishing content to the Hive blockchain directly from your WordPress dashboard using Hive Keychain.', 'wp-dapp'); ?></p>
                <p><?php _e('Version:', 'wp-dapp'); ?> <?php echo WPDAPP_VERSION; ?></p>
                <p><a href="https://diggndeeper.com/wp-dapp/" target="_blank"><?php _e('Plugin Website', 'wp-dapp'); ?></a> | <a href="https://github.com/DiggnDeeper/wp-dapp" target="_blank"><?php _e('GitHub Repository', 'wp-dapp'); ?></a></p>
            </div>
        </div>
        <?php
    }
}
