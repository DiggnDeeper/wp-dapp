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
        
        $json_metadata = json_encode([
            'tags' => isset($post_data['tags']) ? array_map('sanitize_text_field', $post_data['tags']) : [],
            'app' => 'wp-dapp/0.1'
        ]);

        $post_data = [
            'jsonrpc' => '2.0',
            'method' => 'broadcast_transaction',
            'params' => [
                [
                    'operations' => [[
                        'comment',
                        [
                            'parent_author' => '',
                            'parent_permlink' => isset($post_data['tags'][0]) ? $post_data['tags'][0] : 'blog',
                            'author' => $this->account,
                            'permlink' => $permlink,
                            'title' => $post_data['title'],
                            'body' => $post_data['body'],
                            'json_metadata' => $json_metadata
                        ]
                    ]]
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
            'author' => $this->account
        ];
    }

    private function create_permlink($title) {
        $permlink = sanitize_title($title);
        $permlink = strtolower($permlink);
        $permlink = preg_replace('/[^a-z0-9-]/', '', $permlink);
        return $permlink . '-' . date('Ymd');
    }
}

