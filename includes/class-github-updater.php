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
     * Whether the updater is active
     *
     * @var bool
     */
    private $is_active = false;

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
        // Don't run setup on plugin activation - wait until next page load
        // This prevents fatal errors during activation
        if (is_admin() && !defined('WP_DAPP_ACTIVATING')) {
            // We'll defer the setup until plugins_loaded to ensure WordPress is fully loaded
            add_action('plugins_loaded', array($this, 'setup_updater'), 20);
        }
    }

    /**
     * Initialize the updater.
     * 
     * @return void
     */
    public function setup_updater() {
        try {
            // Check if all required files exist
            $this->check_required_files();
            
            // We'll only proceed if all files are present
            if ($this->is_active) {
                $this->initialize_update_checker();
            }
        } catch (Exception $e) {
            // Log or display the error in a non-fatal way
            add_action('admin_notices', array($this, 'display_error_notice'));
        }
    }
    
    /**
     * Check if all required files for the updater exist.
     * 
     * @return bool True if all required files exist
     */
    private function check_required_files() {
        $base_dir = WPDAPP_PLUGIN_DIR . 'includes/plugin-update-checker/';
        
        // List of required files
        $required_files = array(
            'plugin-update-checker.php',
            'load-v5p5.php',
            'Puc/v5p5/Vcs/GitHubApi.php',
            'Puc/v5p5/Vcs/PluginUpdateChecker.php',
            'Puc/v5p5/Plugin/UpdateChecker.php'
        );
        
        // Check each file
        foreach ($required_files as $file) {
            if (!file_exists($base_dir . $file)) {
                // Store the missing file for error reporting
                $this->missing_file = $file;
                return false;
            }
        }
        
        // All files exist, we can proceed
        $this->is_active = true;
        return true;
    }
    
    /**
     * Initialize the update checker.
     * 
     * @return void
     */
    private function initialize_update_checker() {
        // Safety measure - double check the main file exists
        $puc_file = WPDAPP_PLUGIN_DIR . 'includes/plugin-update-checker/plugin-update-checker.php';
        
        if (file_exists($puc_file)) {
            try {
                // Include the library
                require_once $puc_file;
                
                // Determine which factory class to use
                $factory = $this->get_factory_class();
                
                if (empty($factory)) {
                    // No factory class found, can't proceed
                    $this->is_active = false;
                    add_action('admin_notices', array($this, 'updater_class_missing_notice'));
                    return;
                }
                
                // Make sure the Vcs components are loaded
                $this->ensure_vcs_loaded();
                
                // Initialize the update checker with direct instantiation to avoid factory issues
                $repo_url = 'https://github.com/' . $this->github_owner . '/' . $this->github_repo . '/';
                
                if (class_exists('YahnisElsts\\PluginUpdateChecker\\v5p5\\Vcs\\GitHubApi')) {
                    // Use namespaced classes if available
                    $plugin_file = WPDAPP_PLUGIN_DIR . 'wp-dapp.php';
                    $github_api = new YahnisElsts\PluginUpdateChecker\v5p5\Vcs\GitHubApi($repo_url);
                    $this->update_checker = new YahnisElsts\PluginUpdateChecker\v5p5\Vcs\PluginUpdateChecker(
                        $github_api,
                        $plugin_file,
                        'wp-dapp'
                    );
                } elseif (class_exists('Puc_v5p5_Vcs_GitHubApi')) {
                    // Fall back to non-namespaced classes
                    $plugin_file = WPDAPP_PLUGIN_DIR . 'wp-dapp.php';
                    $github_api = new Puc_v5p5_Vcs_GitHubApi($repo_url);
                    $this->update_checker = new Puc_v5p5_Vcs_PluginUpdateChecker(
                        $github_api,
                        $plugin_file,
                        'wp-dapp'
                    );
                } else {
                    // Last resort: try using the factory method
                    $this->update_checker = $factory::buildUpdateChecker(
                        $repo_url,
                        WPDAPP_PLUGIN_DIR . 'wp-dapp.php',
                        'wp-dapp'
                    );
                }
                
                if ($this->update_checker) {
                    // Set the branch that contains the stable release
                    if (method_exists($this->update_checker, 'setBranch')) {
                        $this->update_checker->setBranch('main');
                    }
        
                    // Try to use release assets if the method exists
                    if (method_exists($this->update_checker, 'getVcsApi') && 
                        method_exists($this->update_checker->getVcsApi(), 'enableReleaseAssets')) {
                        $this->update_checker->getVcsApi()->enableReleaseAssets();
                    }
        
                    // Add filters to modify update checking behavior if the hook exists
                    $filter_name = 'puc_pre_inject_update-wp-dapp';
                    if (!has_action($filter_name)) {
                        add_filter($filter_name, array($this, 'filter_update_info'), 10, 2);
                    }
                } else {
                    $this->is_active = false;
                    add_action('admin_notices', array($this, 'updater_init_failed_notice'));
                }
            } catch (Exception $e) {
                // Silently fail, but record that we're not active
                $this->is_active = false;
                // Store the error message for debugging
                $this->error_message = $e->getMessage();
                add_action('admin_notices', array($this, 'updater_exception_notice'));
            }
        } else {
            // File doesn't exist, add admin notice
            $this->is_active = false;
            add_action('admin_notices', array($this, 'updater_missing_notice'));
        }
    }
    
    /**
     * Ensure all VCS components are loaded
     */
    private function ensure_vcs_loaded() {
        $puc_dir = WPDAPP_PLUGIN_DIR . 'includes/plugin-update-checker/Puc/v5p5/';
        
        // Try to include important VCS files directly to ensure they're available
        $vcs_files = array(
            'Vcs/BaseChecker.php',
            'Vcs/Api.php', 
            'Vcs/PluginUpdateChecker.php',
            'Vcs/GitHubApi.php',
            'Vcs/Reference.php'
        );
        
        foreach ($vcs_files as $file) {
            if (file_exists($puc_dir . $file)) {
                include_once $puc_dir . $file;
            }
        }
    }
    
    /**
     * Get the appropriate factory class for the Plugin Update Checker.
     * 
     * @return string|null The factory class name or null if none found
     */
    private function get_factory_class() {
        $factory_classes = array(
            '\YahnisElsts\PluginUpdateChecker\v5p5\PucFactory',
            'Puc_v5p5_Factory',
            '\YahnisElsts\PluginUpdateChecker\v5\PucFactory',
            'Puc_v5_Factory',
            'Puc_v4_Factory',
            'PucFactory'
        );
        
        foreach ($factory_classes as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }
        
        return null;
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
        <div class="notice notice-warning">
            <p><?php _e('WP-Dapp: GitHub updater library is missing. Plugin will still function, but automatic updates will not work.', 'wpdapp'); ?></p>
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
        <div class="notice notice-warning">
            <p><?php _e('WP-Dapp: GitHub updater factory class not found. Plugin will still function, but automatic updates will not work.', 'wpdapp'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Display admin notice if the updater initialization failed.
     *
     * @return void
     */
    public function updater_init_failed_notice() {
        ?>
        <div class="notice notice-warning">
            <p><?php _e('WP-Dapp: GitHub updater initialization failed. Plugin will still function, but automatic updates will not work.', 'wpdapp'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Display admin notice if there was an exception.
     *
     * @return void
     */
    public function updater_exception_notice() {
        ?>
        <div class="notice notice-warning">
            <p><?php _e('WP-Dapp: GitHub updater encountered an error: ', 'wpdapp'); ?> <?php echo isset($this->error_message) ? esc_html($this->error_message) : 'Unknown error'; ?></p>
        </div>
        <?php
    }
    
    /**
     * Display error notice.
     *
     * @return void
     */
    public function display_error_notice() {
        ?>
        <div class="notice notice-warning">
            <p><?php _e('WP-Dapp: There was an issue initializing the GitHub updater. Plugin will still function, but automatic updates may not work correctly.', 'wpdapp'); ?></p>
        </div>
        <?php
    }
}

// Initialize the updater
WP_Dapp_GitHub_Updater::get_instance(); 