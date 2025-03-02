<?php
/**
 * Simple Update Checker Class
 * 
 * Provides a lightweight update checking mechanism by directly checking
 * the GitHub repository releases page.
 * 
 * @package WP-Dapp
 * @since 0.5.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class WP_Dapp_Update_Checker {

    /**
     * Current plugin version
     *
     * @var string
     */
    private $current_version;

    /**
     * GitHub repository URL
     *
     * @var string
     */
    private $repo_url;

    /**
     * Transient name for storing update data
     *
     * @var string
     */
    private $transient_name = 'wpdapp_update_data';

    /**
     * How often to check for updates (in seconds)
     * Default: 12 hours
     *
     * @var int
     */
    private $check_interval = 43200;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->current_version = WPDAPP_VERSION;
        $this->repo_url = WPDAPP_REPO_URL;

        // Only run checks in admin
        if (is_admin()) {
            // Check for updates
            add_action('admin_init', array($this, 'check_for_updates'));
            
            // Display update notice
            add_action('admin_notices', array($this, 'display_update_notice'));
        }
    }

    /**
     * Check for updates from GitHub
     */
    public function check_for_updates() {
        // Only check periodically to avoid excessive API calls
        $update_data = get_transient($this->transient_name);
        
        if ($update_data === false) {
            $update_data = $this->get_github_update_data();
            
            // Store the result in a transient
            set_transient($this->transient_name, $update_data, $this->check_interval);
        }
    }
    
    /**
     * Get update data from GitHub
     * 
     * @return array|false Update data or false on failure
     */
    private function get_github_update_data() {
        $update_data = array(
            'new_version' => '',
            'download_url' => '',
            'has_update' => false,
        );
        
        // GitHub API URL for the latest release
        $api_url = 'https://api.github.com/repos/DiggnDeeper/wp-dapp/releases/latest';
        
        // Make the request with a proper user agent
        $response = wp_remote_get($api_url, array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url(),
            )
        ));
        
        // Check for errors
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }
        
        // Parse the response body
        $release_data = json_decode(wp_remote_retrieve_body($response), true);
        
        // Validate the response
        if (empty($release_data) || !isset($release_data['tag_name']) || !isset($release_data['html_url'])) {
            return false;
        }
        
        // Clean version number (remove 'v' prefix if present)
        $latest_version = ltrim($release_data['tag_name'], 'v');
        
        // Compare versions
        if (version_compare($latest_version, $this->current_version, '>')) {
            $update_data['new_version'] = $latest_version;
            $update_data['download_url'] = $release_data['html_url'];
            $update_data['has_update'] = true;
        }
        
        return $update_data;
    }
    
    /**
     * Display update notice in admin
     */
    public function display_update_notice() {
        // Only show on our plugin pages or plugins page
        $screen = get_current_screen();
        $show_on_screens = array(
            'plugins',
            'settings_page_wpdapp-settings',
        );
        
        if (!in_array($screen->id, $show_on_screens)) {
            return;
        }
        
        $update_data = get_transient($this->transient_name);
        
        if (!empty($update_data) && $update_data['has_update']) {
            ?>
            <div class="notice notice-info is-dismissible">
                <p>
                    <strong>WP-Dapp Update Available:</strong> 
                    Version <?php echo esc_html($update_data['new_version']); ?> is available. 
                    <a href="<?php echo esc_url($update_data['download_url']); ?>" target="_blank">
                        View release details and download
                    </a>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Force an update check by clearing the transient
     */
    public static function force_update_check() {
        delete_transient('wpdapp_update_data');
    }
} 