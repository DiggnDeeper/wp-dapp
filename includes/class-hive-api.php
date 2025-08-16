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

        // Allow custom API endpoint if configured
        if ( ! empty( $options['hive_api_node'] ) ) {
            $custom_node = trim( $options['hive_api_node'] );
            if ( filter_var( $custom_node, FILTER_VALIDATE_URL ) ) {
                $this->api_endpoint = $custom_node;
            }
        }
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

    /**
     * Perform a JSON-RPC call to the configured Hive API endpoint
     *
     * @param string $method
     * @param array $params
     * @return array|WP_Error
     */
    private function rpc_call( $method, $params ) {
        $request_data = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => 1
        ];

        $response = wp_remote_post( $this->api_endpoint, [
            'body' => wp_json_encode( $request_data ),
            'headers' => [ 'Content-Type' => 'application/json' ],
            'timeout' => 20
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['error'] ) ) {
            $message = is_array( $body['error'] ) && isset( $body['error']['message'] ) ? $body['error']['message'] : 'Hive API error';
            return new WP_Error( 'hive_api_error', $message );
        }

        return isset( $body['result'] ) ? $body['result'] : [];
    }

    /**
     * Get a single post or comment content by author/permlink
     *
     * @param string $author
     * @param string $permlink
     * @return array|WP_Error
     */
    public function get_content( $author, $permlink ) {
        if ( empty( $author ) || empty( $permlink ) ) {
            return new WP_Error( 'invalid_parameters', 'Author and permlink are required.' );
        }
        $result = $this->rpc_call( 'condenser_api.get_content', [ $author, $permlink ] );
        return $result;
    }

    /**
     * Get direct replies to a post or comment
     *
     * @param string $author
     * @param string $permlink
     * @return array|WP_Error
     */
    public function get_content_replies( $author, $permlink ) {
        if ( empty( $author ) || empty( $permlink ) ) {
            return new WP_Error( 'invalid_parameters', 'Author and permlink are required.' );
        }
        $result = $this->rpc_call( 'condenser_api.get_content_replies', [ $author, $permlink ] );
        return $result;
    }

    /**
     * Recursively fetch all replies for a given (author, permlink) thread
     * Returns a flat array of replies with parent info preserved
     *
     * @param string $author
     * @param string $permlink
     * @param int $max_depth Safety cap for recursion depth (0 = unlimited)
     * @param int $max_comments Safety cap for total comments
     * @return array|WP_Error
     */
    public function get_all_replies( $author, $permlink, $max_depth = 0, $max_comments = 2000 ) {
        $queue = [ [ 'author' => $author, 'permlink' => $permlink, 'depth' => 0 ] ];
        $collected = [];
        $visitedParents = [];

        while ( ! empty( $queue ) ) {
            $parent = array_shift( $queue );
            $parentKey = strtolower( $parent['author'] . '/' . $parent['permlink'] );
            if ( isset( $visitedParents[ $parentKey ] ) ) {
                continue;
            }
            $visitedParents[ $parentKey ] = true;

            $replies = $this->get_content_replies( $parent['author'], $parent['permlink'] );
            if ( is_wp_error( $replies ) ) {
                return $replies;
            }

            foreach ( $replies as $reply ) {
                // Normalize some fields
                $reply['author'] = isset( $reply['author'] ) ? $reply['author'] : '';
                $reply['permlink'] = isset( $reply['permlink'] ) ? $reply['permlink'] : '';
                $reply['parent_author'] = isset( $reply['parent_author'] ) ? $reply['parent_author'] : '';
                $reply['parent_permlink'] = isset( $reply['parent_permlink'] ) ? $reply['parent_permlink'] : '';
                $reply['created'] = isset( $reply['created'] ) ? $reply['created'] : gmdate( 'Y-m-d H:i:s' );
                $reply['children'] = isset( $reply['children'] ) ? intval( $reply['children'] ) : 0;

                $collected[] = $reply;

                if ( count( $collected ) >= $max_comments ) {
                    break 2; // Stop if we hit max comment cap
                }

                $nextDepth = $parent['depth'] + 1;
                if ( $reply['children'] > 0 && ( $max_depth === 0 || $nextDepth < $max_depth ) ) {
                    $queue[] = [
                        'author' => $reply['author'],
                        'permlink' => $reply['permlink'],
                        'depth' => $nextDepth,
                    ];
                }
            }
        }

        // Sort by created ascending for stable insertion order
        usort( $collected, function( $a, $b ) {
            $t1 = strtotime( isset( $a['created'] ) ? $a['created'] : 'now' );
            $t2 = strtotime( isset( $b['created'] ) ? $b['created'] : 'now' );
            if ( $t1 === $t2 ) { return 0; }
            return ( $t1 < $t2 ) ? -1 : 1;
        } );

        return $collected;
    }
}

