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

        add_settings_section(
            'wpdapp_main_section',
            'Hive Account Settings',
            [$this, 'section_callback'],
            'wpdapp-settings'
        );

        add_settings_field(
            'hive_account',
            'Hive Username',
            [$this, 'render_field'],
            'wpdapp-settings',
            'wpdapp_main_section',
            ['field' => 'hive_account']
        );

        add_settings_field(
            'private_key',
            'Private Posting Key',
            [$this, 'render_field'],
            'wpdapp-settings',
            'wpdapp_main_section',
            ['field' => 'private_key']
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
        $sanitized['hive_account'] = sanitize_text_field($options['hive_account']);
        $sanitized['private_key'] = sanitize_text_field($options['private_key']);
        return $sanitized;
    }

    /**
     * Print the Section text.
     */
    public function section_callback() {
        echo '<p>Enter your Hive account credentials below:</p>';
    }

    /**
     * Hive account field callback.
     */
    public function render_field($args) {
        $options = get_option('wpdapp_options');
        $value = isset($options[$args['field']]) ? $options[$args['field']] : '';
        $type = $args['field'] === 'private_key' ? 'password' : 'text';
        printf(
            '<input type="%s" name="wpdapp_options[%s]" value="%s" class="regular-text">',
            esc_attr($type),
            esc_attr($args['field']),
            esc_attr($value)
        );
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
