<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WP_Dapp_Hive_API {

    protected $account;
    protected $private_key;
    protected $api_endpoint = 'https://api.hive.blog';

    public function __construct() {
        // Retrieve stored options.
        $options = get_option( 'wpdapp_options' );

        $this->account = ! empty( $options['hive_account'] ) ? sanitize_text_field($options['hive_account']) : '';
        $this->private_key = ! empty( $options['private_key'] ) ? sanitize_text_field($options['private_key']) : '';

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
            'app' => 'wp-dapp/0.1'
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

    private function create_permlink($title) {
        $permlink = sanitize_title($title);
        $permlink = strtolower($permlink);
        $permlink = preg_replace('/[^a-z0-9-]/', '', $permlink);
        return $permlink . '-' . date('Ymd');
    }
}

