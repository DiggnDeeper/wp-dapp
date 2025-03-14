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
            'WP-Dapp Settings',
            'WP-Dapp',
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
        
        // Add inline script to FORCE auto_publish to be unchecked by default
        wp_add_inline_script('wpdapp-keychain-integration', '
            jQuery(document).ready(function($) {
                // Force the auto_publish checkbox to be unchecked
                $("#auto_publish").prop("checked", false);
                
                // Also update the database option via AJAX
                $.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: {
                        action: "wpdapp_reset_auto_publish",
                        nonce: "' . wp_create_nonce('wpdapp_reset_auto_publish') . '"
                    },
                    success: function(response) {
                        console.log("Auto-publish option reset successfully");
                    }
                });
            });
        ');
    }

    /**
     * Register and add settings.
     */
    public function register_settings() {
        register_setting('wpdapp_options_group', 'wpdapp_options');
        
        // Account Settings Section
        add_settings_section(
            'wpdapp_account_section',
            'Hive Account Settings',
            [$this, 'account_section_callback'],
            'wpdapp-settings'
        );
        
        add_settings_field(
            'hive_account',
            'Hive Account',
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
            'Enable Default Beneficiary',
            [$this, 'render_field'],
            'wpdapp-settings',
            'wpdapp_beneficiary_section',
            ['field' => 'enable_default_beneficiary', 'type' => 'checkbox']
        );
        
        add_settings_field(
            'default_beneficiary_account',
            'Default Beneficiary Account',
            [$this, 'render_field'],
            'wpdapp-settings',
            'wpdapp_beneficiary_section',
            ['field' => 'default_beneficiary_account']
        );
        
        add_settings_field(
            'default_beneficiary_weight',
            'Default Beneficiary Percentage',
            [$this, 'render_field'],
            'wpdapp-settings',
            'wpdapp_beneficiary_section',
            [
                'field' => 'default_beneficiary_weight', 
                'type' => 'number',
                'min' => 0.1,
                'max' => 10,
                'step' => 0.1,
                'description' => 'Percentage of rewards (0.1-10%). Default is 1%.',
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
            'Default Tags',
            [$this, 'render_field'],
            'wpdapp-settings',
            'wpdapp_post_section',
            [
                'field' => 'default_tags',
                'description' => 'Comma-separated list of default tags to use when none are specified.'
            ]
        );
        
        add_settings_field(
            'auto_publish',
            'Auto-Publish',
            [$this, 'render_field'],
            'wpdapp-settings',
            'wpdapp_post_section',
            [
                'field' => 'auto_publish',
                'type' => 'checkbox',
                'description' => '(Opt-in Feature) Automatically mark newly published WordPress posts for Hive publication. Note: This is disabled by default for your safety.'
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
            'Hive API Node',
            [$this, 'render_field'],
            'wpdapp-settings',
            'wpdapp_advanced_section',
            [
                'field' => 'hive_api_node',
                'description' => 'Custom Hive API node URL. Leave blank to use the default (api.hive.blog).'
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
        
        // Auto-publish: explicitly set to 0 if not checked
        $sanitized['auto_publish'] = isset($options['auto_publish']) && $options['auto_publish'] ? 1 : 0;
        
        // Advanced settings
        $sanitized['hive_api_node'] = sanitize_text_field($options['hive_api_node']);
        
        return $sanitized;
    }

    /**
     * Print the Section text.
     */
    public function account_section_callback() {
        echo '<div class="wpdapp-settings-section-description">';
        echo '<p>Configure your Hive account settings for publishing to the Hive blockchain.</p>';
        echo '</div>';
    }
    
    /**
     * Print the Beneficiary Section text.
     */
    public function beneficiary_section_callback() {
        echo '<div class="wpdapp-settings-section-description">';
        echo '<p>Configure beneficiaries for your Hive posts. Beneficiaries receive a percentage of post rewards.</p>';
        echo '<p><em>Note: A small percentage can be automatically set to support the WP-Dapp plugin development.</em></p>';
        echo '</div>';
    }
    
    /**
     * Print the Post Section text.
     */
    public function post_section_callback() {
        echo '<div class="wpdapp-settings-section-description">';
        echo '<p>Configure post settings for your Hive posts.</p>';
        echo '</div>';
    }
    
    /**
     * Print the Advanced Section text.
     */
    public function advanced_section_callback() {
        echo '<div class="wpdapp-settings-section-description">';
        echo '<p>Advanced settings for the WP-Dapp plugin.</p>';
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
            <p class="description">Click this button to verify your Hive account with Keychain.</p>
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
                // Special handling for auto_publish to ensure it's off by default
                if ($field === 'auto_publish') {
                    // ALWAYS force it to be unchecked in the UI
                    printf(
                        '<label for="%s"><input type="checkbox" id="%s" name="wpdapp_options[%s]" value="1" %s> %s</label>',
                        esc_attr($field),
                        esc_attr($field),
                        esc_attr($field),
                        '', // Never checked
                        isset($args['label']) ? esc_html($args['label']) : ''
                    );
                } else {
                    // Regular checkbox handling for other fields
                    printf(
                        '<label for="%s"><input type="checkbox" id="%s" name="wpdapp_options[%s]" value="1" %s> %s</label>',
                        esc_attr($field),
                        esc_attr($field),
                        esc_attr($field),
                        checked(1, $value, false),
                        isset($args['label']) ? esc_html($args['label']) : ''
                    );
                }
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
                <h3>About WP-Dapp</h3>
                <p>WP-Dapp is a WordPress plugin that enables publishing content to the Hive blockchain directly from your WordPress dashboard using Hive Keychain.</p>
                <p>Version: <?php echo WPDAPP_VERSION; ?></p>
                <p><a href="https://diggndeeper.com/wp-dapp/" target="_blank">Plugin Website</a> | <a href="https://github.com/DiggnDeeper/wp-dapp" target="_blank">GitHub Repository</a></p>
            </div>
        </div>
        <?php
    }
}
