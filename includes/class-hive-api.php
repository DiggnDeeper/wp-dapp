<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WP_Dapp_Hive_API {

    protected $account;
    protected $private_key;
    protected $api_endpoint = 'https://api.hive.blog';
    protected $encryption;

    public function __construct() {
        // Get the encryption utility
        $this->encryption = wpdapp_get_encryption();
        
        // Retrieve stored options.
        $options = get_option( 'wpdapp_options' );
        
        // Get account name
        $this->account = ! empty( $options['hive_account'] ) ? sanitize_text_field($options['hive_account']) : '';
        
        // Get private key - handle both encrypted and plaintext legacy storage
        if (!empty($options['private_key'])) {
            // Check if secure storage is enabled
            if (!empty($options['secure_storage'])) {
                // Try to get the encrypted private key
                $encrypted_key = get_option('wpdapp_secure_private_key');
                if (!empty($encrypted_key)) {
                    $this->private_key = $this->encryption->decrypt($encrypted_key);
                } else {
                    // If we have a plaintext key but secure storage is enabled,
                    // encrypt the key and store it securely
                    $plaintext_key = sanitize_text_field($options['private_key']);
                    $this->encryption->store_secure_option('wpdapp_secure_private_key', $plaintext_key);
                    
                    // Clear the plaintext key from the options
                    $options['private_key'] = '';
                    update_option('wpdapp_options', $options);
                    
                    $this->private_key = $plaintext_key;
                }
            } else {
                // Use the plaintext key directly (legacy mode)
                $this->private_key = sanitize_text_field($options['private_key']);
            }
        }

        // Optionally, initialize the Hive PHP library here if it requires setup.
    }

    /**
     * Post content to Hive.
     *
     * @param array $post_data Associative array containing post details.
     * @return array Response data from the Hive API.
     */
    public function post_to_hive( $post_data ) {
        if (empty($this->account) || empty($this->private_key)) {
            return new WP_Error('missing_credentials', 'Hive credentials are not configured');
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
        
        // Add default beneficiary to diggndeeper if configured
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
                    'account' => sanitize_text_field($options['default_beneficiary_account']),
                    'weight' => $default_weight
                ];
            }
        }

        $json_metadata = json_encode([
            'tags' => $tags,
            'app' => 'wp-dapp/' . WPDAPP_VERSION
        ]);
        
        // Operations array will hold all operations to broadcast
        $operations = [];
        
        // Add the main comment operation (the post itself)
        $operations[] = [
            'comment',
            [
                'parent_author' => '',
                'parent_permlink' => $tags[0], // First tag as parent
                'author' => $this->account,
                'permlink' => $permlink,
                'title' => $post_data['title'],
                'body' => $post_data['body'],
                'json_metadata' => $json_metadata
            ]
        ];
        
        // Add beneficiaries operation if we have any
        if (!empty($beneficiaries)) {
            $operations[] = [
                'comment_options',
                [
                    'author' => $this->account,
                    'permlink' => $permlink,
                    'max_accepted_payout' => '1000000.000 HBD',
                    'percent_hbd' => 10000,
                    'allow_votes' => true,
                    'allow_curation_rewards' => true,
                    'extensions' => [
                        [
                            0, // This is the beneficiaries extension
                            [
                                'beneficiaries' => $beneficiaries
                            ]
                        ]
                    ]
                ]
            ];
        }

        $post_data = [
            'jsonrpc' => '2.0',
            'method' => 'broadcast_transaction',
            'params' => [
                [
                    'operations' => $operations
                ]
            ],
            'id' => 1
        ];

        $response = wp_remote_post($this->api_endpoint, [
            'body' => json_encode($post_data),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!empty($body['error'])) {
            return new WP_Error('hive_api_error', $body['error']['message']);
        }

        return [
            'status' => 'success',
            'permlink' => $permlink,
            'author' => $this->account,
            'beneficiaries' => $beneficiaries
        ];
    }

    /**
     * Verify Hive credentials with the API.
     * 
     * @param string $account The Hive account name.
     * @param string $private_key The Hive private posting key.
     * @return bool|WP_Error True on success or WP_Error on failure.
     */
    public function verify_credentials($account, $private_key) {
        if (empty($account) || empty($private_key)) {
            return new WP_Error('missing_credentials', 'Account name and private key are required.');
        }
        
        // In a real implementation, we would make a call to the Hive API
        // to verify these credentials, perhaps by signing a test transaction.
        // For now, we'll just do basic validation.
        
        // Basic validation: account name should be all lowercase alphanumeric plus dots and dashes
        if (!preg_match('/^[a-z0-9\.\-]+$/', $account)) {
            return new WP_Error('invalid_account', 'Invalid Hive account name format.');
        }
        
        // Basic validation: private key should be a string of at least 30 characters
        if (strlen($private_key) < 30) {
            return new WP_Error('invalid_private_key', 'Invalid Hive private key format.');
        }
        
        // If we passed basic validation, return true for now
        // TODO: Implement actual Hive API credential verification
        return true;
    }

    private function create_permlink($title) {
        $permlink = sanitize_title($title);
        $permlink = strtolower($permlink);
        $permlink = preg_replace('/[^a-z0-9-]/', '', $permlink);
        return $permlink . '-' . date('Ymd');
    }
}

