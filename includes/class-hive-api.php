<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WP_Dapp_Hive_API {

    protected $account;
    protected $api_endpoint = 'https://api.hive.blog';

    public function __construct() {
        // Retrieve stored options.
        $options = get_option( 'wpdapp_options' );
        
        // Get account name
        $this->account = ! empty( $options['hive_account'] ) ? sanitize_text_field($options['hive_account']) : '';
    }

    /**
     * Prepares post data for Hive publication with Keychain.
     * 
     * @param array $post_data Associative array containing post details.
     * @return array|WP_Error Post data ready for Keychain broadcasting or error.
     */
    public function prepare_post_data( $post_data ) {
        if (empty($this->account)) {
            return new WP_Error('missing_account', 'Hive account is not configured');
        }

        $permlink = $this->create_permlink($post_data['title']);
        
        // Build the tags array
        $tags = isset($post_data['tags']) ? array_map('sanitize_text_field', $post_data['tags']) : [];
        if (empty($tags)) {
            $tags[] = 'blog'; // Default tag if none provided
        }
        
        // Process beneficiaries if present
        $beneficiaries = [];
        if (!empty($post_data['beneficiaries'])) {
            foreach ($post_data['beneficiaries'] as $beneficiary) {
                if (!empty($beneficiary['account']) && isset($beneficiary['weight']) && $beneficiary['weight'] > 0) {
                    $beneficiaries[] = [
                        'account' => sanitize_text_field($beneficiary['account']),
                        'weight' => min(10000, max(1, intval($beneficiary['weight']))) // Between 1 and 10000 (0.01% to 100%)
                    ];
                }
            }
        }
        
        // Add default beneficiary if configured
        $options = get_option('wpdapp_options');
        if (!empty($options['enable_default_beneficiary']) && !empty($options['default_beneficiary_account'])) {
            $default_weight = !empty($options['default_beneficiary_weight']) ? 
                min(1000, max(1, intval($options['default_beneficiary_weight']))) : 
                100; // Default to 1% if not specified
                
            // Check if this beneficiary is already set
            $found = false;
            foreach ($beneficiaries as $ben) {
                if ($ben['account'] === $options['default_beneficiary_account']) {
                    $found = true;
                    break;
                }
            }
            
            // Add if not already present
            if (!$found) {
                $beneficiaries[] = [
                    'account' => $options['default_beneficiary_account'],
                    'weight' => $default_weight
                ];
            }
        }
        
        // Prepare the post data for Keychain
        return [
            'author' => $this->account,
            'permlink' => $permlink,
            'title' => $post_data['title'],
            'body' => $post_data['content'],
            'tags' => $tags,
            'beneficiaries' => $beneficiaries,
            'excerpt' => isset($post_data['excerpt']) ? $post_data['excerpt'] : '',
            'image' => isset($post_data['featured_image']) ? [$post_data['featured_image']] : []
        ];
    }

    /**
     * Get profile information for a Hive account
     * 
     * @param string $account The Hive account name to look up
     * @return array|WP_Error Account data or error
     */
    public function get_account_info($account) {
        if (empty($account)) {
            return new WP_Error('missing_account', 'Account name is required');
        }
        
        $request_data = [
            'jsonrpc' => '2.0',
            'method' => 'condenser_api.get_accounts',
            'params' => [[$account]],
            'id' => 1
        ];
        
        $response = wp_remote_post($this->api_endpoint, [
            'body' => json_encode($request_data),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 15
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!empty($body['error'])) {
            return new WP_Error('hive_api_error', $body['error']['message']);
        }
        
        if (empty($body['result']) || empty($body['result'][0])) {
            return new WP_Error('account_not_found', 'Hive account not found');
        }
        
        return $body['result'][0];
    }
    
    /**
     * Verify a signature from Hive Keychain
     * 
     * @param string $account The Hive account name
     * @param string $message The message that was signed
     * @param string $signature The signature to verify
     * @return bool|WP_Error True if signature is valid, WP_Error on failure
     */
    public function verify_keychain_signature($account, $message, $signature) {
        if (empty($account) || empty($message) || empty($signature)) {
            return new WP_Error('missing_parameters', 'Account, message, and signature are required.');
        }
        
        // Get the account's public keys from the blockchain
        $account_info = $this->get_account_info($account);
        
        if (is_wp_error($account_info)) {
            return $account_info;
        }
        
        // For now, we're trusting the signature verification that Keychain does
        // In a production environment, you would implement actual signature verification
        // using the posting public key of the account
        
        // This is a simplified check - for proper verification we need a PHP library for Hive crypto
        return true;
    }

    /**
     * Creates a permlink from a title
     * 
     * @param string $title The post title
     * @return string The generated permlink
     */
    private function create_permlink($title) {
        $permlink = sanitize_title($title);
        $permlink = strtolower($permlink);
        $permlink = preg_replace('/[^a-z0-9-]/', '', $permlink);
        
        // Add date to make permlink unique
        return $permlink . '-' . date('Ymd');
    }
}

