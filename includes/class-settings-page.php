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
        $sanitized['default_beneficiary_weight'] = min(1000, max(1, intval($options['default_beneficiary_weight'] * 100)));
        
        // Publishing settings
        $sanitized['enable_custom_tags'] = isset($options['enable_custom_tags']) ? 1 : 0;
        $sanitized['default_tags'] = sanitize_text_field($options['default_tags']);
        
        // Advanced settings
        $sanitized['custom_api_node'] = sanitize_text_field($options['custom_api_node']);
        $sanitized['delete_data_on_uninstall'] = isset($options['delete_data_on_uninstall']) ? 1 : 0;
        
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
     * Render Keychain status field.
     */
    public function render_keychain_status() {
        ?>
        <div id="wpdapp-keychain-status" class="wpdapp-status-pending">
            <span>Checking for Hive Keychain...</span>
        </div>
        <?php
    }

    /**
     * Render verify button field.
     */
    public function render_verify_button() {
        $options = get_option('wpdapp_options');
        $account = isset($options['hive_account']) ? $options['hive_account'] : '';
        $disabled = empty($account) ? 'disabled' : '';
        ?>
        <button type="button" id="wpdapp-verify-account" class="button button-secondary" <?php echo $disabled; ?>>
            Verify with Keychain
        </button>
        <div id="wpdapp-verify-result"></div>
        <p class="description">Click this button to verify your Hive account with Keychain.</p>
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
        
        if (!empty($args['description'])) {
            printf('<p class="description">%s</p>', $args['description']);
        }
    }

    /**
     * Render the main settings page.
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>WP-Dapp Settings</h1>
            
            <div class="wpdapp-settings-intro">
                <p>Configure your WordPress to Hive integration settings below. Securely authenticate with Hive Keychain.</p>
            </div>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('wpdapp_options_group');
                do_settings_sections('wpdapp-settings');
                submit_button();
                ?>
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
