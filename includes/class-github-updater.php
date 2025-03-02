<?php
/**
 * GitHub Updater Class
 * 
 * Handles automatic updates from a GitHub repository.
 * 
 * @package WP-Dapp
 * @since 0.4
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class WP_Dapp_GitHub_Updater {

    /**
     * The single instance of the class.
     *
     * @var WP_Dapp_GitHub_Updater
     */
    private static $instance = null;

    /**
     * Plugin Update Checker instance
     *
     * @var object
     */
    private $update_checker;

    /**
     * GitHub repository owner
     *
     * @var string
     */
    private $github_owner = 'DiggnDeeper';

    /**
     * GitHub repository name
     *
     * @var string
     */
    private $github_repo = 'wp-dapp';

    /**
     * Get class instance.
     *
     * @return WP_Dapp_GitHub_Updater
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     * 
     * Sets up the GitHub updater.
     */
    public function __construct() {
        // Only set up the updater if we're in admin
        if (is_admin()) {
            $this->setup_updater();
        }
    }

    /**
     * Initialize the updater.
     * 
     * @return void
     */
    private function setup_updater() {
        // Check if the plugin update checker file exists
        $puc_file = WPDAPP_PLUGIN_DIR . 'includes/plugin-update-checker/plugin-update-checker.php';
        
        if (file_exists($puc_file)) {
            // Include the library
            require_once $puc_file;
            
            // The library might use either Puc_v4_Factory or PucFactory depending on version
            if (class_exists('Puc_v4_Factory')) {
                $factory = 'Puc_v4_Factory';
            } elseif (class_exists('\YahnisElsts\PluginUpdateChecker\v5\PucFactory')) {
                $factory = '\YahnisElsts\PluginUpdateChecker\v5\PucFactory';
            } elseif (class_exists('Puc_v5p5_Factory')) {
                $factory = 'Puc_v5p5_Factory';
            } elseif (class_exists('PucFactory')) {
                $factory = 'PucFactory';
            } else {
                // Factory class not found, add admin notice
                add_action('admin_notices', array($this, 'updater_class_missing_notice'));
                return;
            }
            
            // Initialize the update checker
            $this->update_checker = $factory::buildUpdateChecker(
                'https://github.com/' . $this->github_owner . '/' . $this->github_repo . '/',
                WPDAPP_PLUGIN_DIR . 'wp-dapp.php',
                'wp-dapp'
            );

            // Set the branch that contains the stable release
            $this->update_checker->setBranch('main');

            // Optional: Set authentication to increase API rate limit
            // $this->update_checker->setAuthentication('your-github-personal-token');

            // Try to use release assets if the method exists
            if (method_exists($this->update_checker, 'getVcsApi') && 
                method_exists($this->update_checker->getVcsApi(), 'enableReleaseAssets')) {
                $this->update_checker->getVcsApi()->enableReleaseAssets();
            }

            // Add filters to modify update checking behavior if the hook exists
            $filter_name = 'puc_pre_inject_update-wp-dapp';
            if (has_filter($filter_name) || !has_action($filter_name)) {
                add_filter($filter_name, array($this, 'filter_update_info'), 10, 2);
            }
        } else {
            // File doesn't exist, add admin notice
            add_action('admin_notices', array($this, 'updater_missing_notice'));
        }
    }

    /**
     * Filter the update info before it's injected.
     *
     * @param array $update
     * @param array $hook_extra
     * @return array
     */
    public function filter_update_info($update, $hook_extra) {
        // You can modify the update info here if needed
        return $update;
    }

    /**
     * Display admin notice if the updater library is missing.
     *
     * @return void
     */
    public function updater_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('WP-Dapp: GitHub updater library is missing. Plugin updates will not work correctly. Please reinstall the plugin or contact support.', 'wpdapp'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Display admin notice if the updater class is missing.
     *
     * @return void
     */
    public function updater_class_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('WP-Dapp: GitHub updater factory class not found. Plugin updates will not work correctly. Please reinstall the plugin or contact support.', 'wpdapp'); ?></p>
        </div>
        <?php
    }
}

// Initialize the updater
WP_Dapp_GitHub_Updater::get_instance(); 