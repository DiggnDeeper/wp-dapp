<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WP_Dapp_Settings_Page {

    private $options;
    private $encryption;
    private $hive_api;

    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        $this->encryption = wpdapp_get_encryption();
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
            'https://cdn.jsdelivr.net/npm/hive-keychain-browser@1.0.4/index.min.js',
            [],
            '1.0.4',
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
                'description' => 'Percentage of rewards (1-10). Default is 1%.'
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
                'description' => 'Automatically publish to Hive when a post is published in WordPress.'
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
     * Sanitize each setting field as needed.
     *
     * @param array $input Contains all settings fields as array keys.
     * @return array Sanitized settings.
     */
    public function sanitize_options($options) {
        $sanitized = [];
        
        // Get existing options
        $existing_options = get_option('wpdapp_options', []);
        
        // Account settings
        $sanitized['hive_account'] = sanitize_text_field($options['hive_account']);
        
        // Handle private key based on secure storage setting
        $sanitized['secure_storage'] = isset($options['secure_storage']) ? 1 : 0;
        
        if (!empty($options['private_key'])) {
            $private_key = sanitize_text_field($options['private_key']);
            
            // If secure storage is enabled, store the key securely
            if ($sanitized['secure_storage']) {
                $this->encryption->store_secure_option('wpdapp_secure_private_key', $private_key);
                $sanitized['private_key'] = ''; // Don't store in plaintext
            } else {
                $sanitized['private_key'] = $private_key; // Store in plaintext (not recommended)
            }
        } else {
            // If no new key provided, keep the existing one
            if ($existing_options['secure_storage']) {
                $sanitized['private_key'] = '';
            } else {
                $sanitized['private_key'] = isset($existing_options['private_key']) ? $existing_options['private_key'] : '';
            }
        }
        
        // Beneficiary settings
        $sanitized['enable_default_beneficiary'] = isset($options['enable_default_beneficiary']) ? 1 : 0;
        $sanitized['default_beneficiary_account'] = sanitize_text_field($options['default_beneficiary_account']);
        $sanitized['default_beneficiary_weight'] = min(1000, max(1, intval($options['default_beneficiary_weight'] * 100)));
        
        // Publishing settings
        $sanitized['enable_custom_tags'] = isset($options['enable_custom_tags']) ? 1 : 0;
        $sanitized['default_tags'] = sanitize_text_field($options['default_tags']);
        
        // Advanced settings
        $sanitized['delete_data_on_uninstall'] = isset($options['delete_data_on_uninstall']) ? 1 : 0;
        
        // Add validation notice if credentials are provided
        if (!empty($sanitized['hive_account']) && 
            (!empty($sanitized['private_key']) || !empty($options['private_key']))) {
            
            // Get the private key (either from plaintext or encrypted)
            $private_key = !empty($options['private_key']) ? 
                sanitize_text_field($options['private_key']) : 
                $this->encryption->get_secure_option('wpdapp_secure_private_key');
                
            // Verify credentials
            $result = $this->hive_api->verify_credentials($sanitized['hive_account'], $private_key);
            
            if (is_wp_error($result)) {
                add_settings_error(
                    'wpdapp_options',
                    'invalid_credentials',
                    'Hive API Error: ' . $result->get_error_message(),
                    'error'
                );
            } else {
                add_settings_error(
                    'wpdapp_options',
                    'valid_credentials',
                    'Hive credentials verified successfully.',
                    'success'
                );
            }
        }
        
        return $sanitized;
    }

    /**
     * Print the Account Section text.
     */
    public function account_section_callback() {
        echo '<p>Connect your plugin to the Hive blockchain by verifying your Hive account using Hive Keychain.</p>';
        echo '<p>You will need to have the <a href="https://hive-keychain.com/" target="_blank">Hive Keychain browser extension</a> installed to use this plugin.</p>';
    }
    
    /**
     * Print the Beneficiary Section text.
     */
    public function beneficiary_section_callback() {
        echo '<p>Configure beneficiaries for your Hive posts. Beneficiaries receive a percentage of post rewards.</p>';
        echo '<p><em>Note: A small percentage can be automatically set to support the WP-Dapp plugin development.</em></p>';
    }
    
    /**
     * Print the Post Section text.
     */
    public function post_section_callback() {
        echo '<p>Configure post settings for your Hive posts.</p>';
    }
    
    /**
     * Print the Advanced Section text.
     */
    public function advanced_section_callback() {
        echo '<p>Advanced settings for the WP-Dapp plugin.</p>';
    }

    /**
     * Render the Keychain status field.
     */
    public function render_keychain_status() {
        echo '<div id="wpdapp-keychain-status"></div>';
    }

    /**
     * Render the verify button.
     */
    public function render_verify_button() {
        echo '<button id="wpdapp-verify-account" class="button">Verify with Keychain</button>';
        echo '<div id="wpdapp-credential-status" style="margin-top: 8px;"></div>';
    }

    /**
     * Render settings field.
     */
    public function render_field($args) {
        $options = get_option('wpdapp_options');
        $field = $args['field'];
        $type = isset($args['type']) ? $args['type'] : 'text';
        $value = isset($options[$field]) ? $options[$field] : '';
        
        // Special handling for private_key field with secure storage
        if ($field === 'private_key' && !empty($options['secure_storage']) && empty($value)) {
            $has_secure_key = !empty($this->encryption->get_secure_option('wpdapp_secure_private_key'));
            if ($has_secure_key) {
                echo '<div class="wpdapp-secure-key-notice">';
                echo '<span class="dashicons dashicons-lock"></span> ';
                echo '<em>Your key is securely stored.</em>';
                echo '</div>';
            }
        }
        
        // Handle different field types
        switch ($type) {
            case 'checkbox':
                printf(
                    '<input type="checkbox" id="%s" name="wpdapp_options[%s]" value="1" %s>',
                    esc_attr($field),
                    esc_attr($field),
                    checked(1, $value, false)
                );
                break;
                
            case 'number':
                $min = isset($args['min']) ? $args['min'] : '';
                $max = isset($args['max']) ? $args['max'] : '';
                
                // Convert weight from internal storage (0-10000) to percentage (0-100)
                if ($field === 'default_beneficiary_weight' && !empty($value)) {
                    $value = $value / 100; // Convert to percentage
                }
                
                printf(
                    '<input type="number" id="%s" name="wpdapp_options[%s]" value="%s" class="small-text" %s %s>',
                    esc_attr($field),
                    esc_attr($field),
                    esc_attr($value),
                    !empty($min) ? "min=\"{$min}\"" : "",
                    !empty($max) ? "max=\"{$max}\"" : ""
                );
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
        
        // Display description if provided
        if (isset($args['description'])) {
            printf('<p class="description">%s</p>', esc_html($args['description']));
        }
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>WP-Dapp Settings</h1>
            
            <div class="wpdapp-settings-intro">
                <p>Configure your WordPress to Hive integration settings below. For security, your private posting key is stored with encryption.</p>
            </div>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('wpdapp_options');
                do_settings_sections('wpdapp-settings');
                submit_button();
                ?>
            </form>
            
            <div class="wpdapp-settings-footer">
                <h3>About WP-Dapp</h3>
                <p>WP-Dapp is a WordPress plugin that enables publishing content to the Hive blockchain directly from your WordPress dashboard.</p>
                <p>Version: <?php echo WPDAPP_VERSION; ?></p>
                <p><a href="https://diggndeeper.com/wp-dapp/" target="_blank">Plugin Website</a> | <a href="https://github.com/DiggnDeeper/wp-dapp" target="_blank">GitHub Repository</a></p>
            </div>
        </div>
        
        <style>
            .wpdapp-settings-intro {
                background: #fff;
                border-left: 4px solid #46b450;
                box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
                margin: 20px 0;
                padding: 1px 12px;
            }
            .wpdapp-settings-footer {
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #ddd;
            }
            .wpdapp-secure-key-notice {
                background: #f8f8f8;
                padding: 5px 10px;
                margin-bottom: 10px;
                display: inline-block;
                border-radius: 4px;
                color: #464646;
            }
            .wpdapp-secure-key-notice .dashicons {
                color: #46b450;
                vertical-align: middle;
            }
            .wpdapp-credential-error {
                background: #fff8f7;
                border-left: 4px solid #dc3232;
                box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
                padding: 1px 12px;
                margin-bottom: 10px;
            }
            .wpdapp-credential-error a {
                font-weight: bold;
            }
        </style>
        <?php
    }
}
