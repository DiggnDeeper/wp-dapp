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
     * @var Puc_v4_Factory
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
        // Only set up the updater if the library exists and we're in admin
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
        // Include the library if it's not already included
        if (!class_exists('Puc_v4_Factory')) {
            require_once WPDAPP_PLUGIN_DIR . 'includes/plugin-update-checker/plugin-update-checker.php';
        }
        
        // Make sure the library was loaded successfully
        if (class_exists('Puc_v4_Factory')) {
            // Initialize the update checker
            $this->update_checker = Puc_v4_Factory::buildUpdateChecker(
                'https://github.com/' . $this->github_owner . '/' . $this->github_repo . '/',
                WPDAPP_PLUGIN_DIR . 'wp-dapp.php',
                'wp-dapp'
            );

            // Set the branch that contains the stable release
            $this->update_checker->setBranch('main');

            // Optional: Set authentication to increase API rate limit
            // $this->update_checker->setAuthentication('your-github-personal-token');

            // Use the zip archive as the download package
            $this->update_checker->getVcsApi()->enableReleaseAssets();

            // Add filters to modify update checking behavior if needed
            add_filter('puc_pre_inject_update-wp-dapp', array($this, 'filter_update_info'), 10, 2);
        } else {
            // Log error or notify admin that the updater library couldn't be loaded
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
            <p><?php _e('WP-Dapp: GitHub updater library is missing. Plugin updates will not work correctly.', 'wpdapp'); ?></p>
        </div>
        <?php
    }
}

// Initialize the updater
WP_Dapp_GitHub_Updater::get_instance(); 