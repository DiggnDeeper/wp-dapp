<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WP_Dapp_Settings_Page {

    private $options;

    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
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
     * Register and add settings.
     */
    public function register_settings() {
        register_setting('wpdapp_options', 'wpdapp_options', [$this, 'sanitize_options']);

        // Account Settings Section
        add_settings_section(
            'wpdapp_account_section',
            'Hive Account Settings',
            [$this, 'account_section_callback'],
            'wpdapp-settings'
        );

        add_settings_field(
            'hive_account',
            'Hive Username',
            [$this, 'render_field'],
            'wpdapp-settings',
            'wpdapp_account_section',
            ['field' => 'hive_account']
        );

        add_settings_field(
            'private_key',
            'Private Posting Key',
            [$this, 'render_field'],
            'wpdapp-settings',
            'wpdapp_account_section',
            ['field' => 'private_key', 'type' => 'password']
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
                'min' => '1',
                'max' => '10',
                'description' => 'Percentage of rewards (1-10%)'
            ]
        );
        
        // Publishing Settings Section
        add_settings_section(
            'wpdapp_publishing_section',
            'Publishing Settings',
            [$this, 'publishing_section_callback'],
            'wpdapp-settings'
        );
        
        add_settings_field(
            'enable_custom_tags',
            'Enable Custom Tags',
            [$this, 'render_field'],
            'wpdapp-settings',
            'wpdapp_publishing_section',
            ['field' => 'enable_custom_tags', 'type' => 'checkbox']
        );
        
        add_settings_field(
            'default_tags',
            'Default Tags',
            [$this, 'render_field'],
            'wpdapp-settings',
            'wpdapp_publishing_section',
            [
                'field' => 'default_tags',
                'description' => 'Comma-separated list of default tags to include in all posts'
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
        
        // Account settings
        $sanitized['hive_account'] = sanitize_text_field($options['hive_account']);
        $sanitized['private_key'] = sanitize_text_field($options['private_key']);
        
        // Beneficiary settings
        $sanitized['enable_default_beneficiary'] = isset($options['enable_default_beneficiary']) ? 1 : 0;
        $sanitized['default_beneficiary_account'] = sanitize_text_field($options['default_beneficiary_account']);
        $sanitized['default_beneficiary_weight'] = min(1000, max(1, intval($options['default_beneficiary_weight'] * 100)));
        
        // Publishing settings
        $sanitized['enable_custom_tags'] = isset($options['enable_custom_tags']) ? 1 : 0;
        $sanitized['default_tags'] = sanitize_text_field($options['default_tags']);
        
        return $sanitized;
    }

    /**
     * Print the Section text.
     */
    public function account_section_callback() {
        echo '<p>Enter your Hive account credentials below:</p>';
    }
    
    /**
     * Print the Beneficiary Section text.
     */
    public function beneficiary_section_callback() {
        echo '<p>Configure beneficiaries for your Hive posts. Beneficiaries receive a percentage of post rewards.</p>';
        echo '<p><em>Note: A small percentage can be automatically set to support the WP-Dapp plugin development.</em></p>';
    }
    
    /**
     * Print the Publishing Section text.
     */
    public function publishing_section_callback() {
        echo '<p>Configure publishing settings for your Hive posts.</p>';
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
                    '<input type="password" id="%s" name="wpdapp_options[%s]" value="%s" class="regular-text">',
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
            <form method="post" action="options.php">
                <?php
                settings_fields('wpdapp_options');
                do_settings_sections('wpdapp-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

// Initialize the settings page.
new WP_Dapp_Settings_Page();
