/**
 * WP-Dapp Keychain Publishing Integration
 * Handles posting to Hive with Keychain for WP-Dapp
 */
jQuery(document).ready(function($) {
    
    // Check if we're on the post editor screen and the WP-Dapp meta box is present
    if ($('#wpdapp-hive-publish').length === 0) {
        return;
    }
    
    // Check if Keychain is available
    const isKeychainAvailable = () => {
        return typeof hive_keychain !== 'undefined';
    };
    
    // Update the Keychain status message
    const updateKeychainStatus = () => {
        const $status = $('#wpdapp-keychain-status');
        
        if (!$status.length) {
            return;
        }
        
        if (isKeychainAvailable()) {
            $status.html('<span class="wpdapp-status-ok">✓ Hive Keychain detected</span>');
            $('#wpdapp-publish-button').prop('disabled', false);
        } else {
            $status.html('<span class="wpdapp-status-error">✗ Hive Keychain not detected</span>' +
                '<p class="description">Please install the <a href="https://hive-keychain.com/" target="_blank">Hive Keychain browser extension</a> to publish to Hive.</p>');
            $('#wpdapp-publish-button').prop('disabled', true);
        }
    };
    
    // Initialize
    updateKeychainStatus();
    
    // Handle the Publish to Hive button click
    $('#wpdapp-publish-button').on('click', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const $status = $('#wpdapp-publish-status');
        const username = wpdapp_publish.hive_account;
        
        // Check if Keychain is available
        if (!isKeychainAvailable()) {
            $status.html('<span class="wpdapp-status-error">Hive Keychain extension not detected</span>');
            return;
        }
        
        // Disable button and show loading indicator
        $button.prop('disabled', true).addClass('loading');
        $status.html('<span class="wpdapp-status-pending">Preparing to publish...</span>');
        
        // Get post data from the server
        $.ajax({
            url: wpdapp_publish.ajax_url,
            type: 'POST',
            data: {
                action: 'wpdapp_prepare_post',
                nonce: wpdapp_publish.nonce,
                post_id: wpdapp_publish.post_id
            },
            success: function(response) {
                if (!response.success) {
                    $status.html('<span class="wpdapp-status-error">' + (response.data || 'Error preparing post data.') + '</span>');
                    $button.prop('disabled', false).removeClass('loading');
                    return;
                }
                
                const postData = response.data;
                
                // Prepare operations for Keychain
                const operations = [];
                
                // Main comment operation
                const commentOp = [
                    'comment',
                    {
                        parent_author: '',
                        parent_permlink: postData.tags[0] || 'blog',
                        author: username,
                        permlink: postData.permlink,
                        title: postData.title,
                        body: postData.body,
                        json_metadata: JSON.stringify({
                            tags: postData.tags,
                            app: 'wp-dapp/0.7.0',
                            format: 'markdown',
                            description: postData.excerpt,
                            image: postData.image || []
                        })
                    }
                ];
                operations.push(commentOp);
                
                // Comment options operation (including beneficiaries if any)
                const commentOptionsOp = [
                    'comment_options',
                    {
                        author: username,
                        permlink: postData.permlink,
                        max_accepted_payout: '1000000.000 HBD',
                        percent_hbd: 10000,
                        allow_votes: true,
                        allow_curation_rewards: true,
                        extensions: []
                    }
                ];
                
                // Add beneficiaries if present
                if (postData.beneficiaries && postData.beneficiaries.length > 0) {
                    const beneficiaries = postData.beneficiaries.map(b => ({
                        account: b.account,
                        weight: parseInt(b.weight, 10)
                    }));
                    
                    commentOptionsOp[1].extensions.push([0, { beneficiaries }]);
                }
                
                operations.push(commentOptionsOp);
                
                // Send broadcast to Keychain
                $status.html('<span class="wpdapp-status-pending">Waiting for approval in Hive Keychain...</span>');
                
                hive_keychain.requestBroadcast(
                    username,
                    operations,
                    'posting',
                    response => {
                        if (response.success) {
                            // Update post meta with successful publish data
                            $.ajax({
                                url: wpdapp_publish.ajax_url,
                                type: 'POST',
                                data: {
                                    action: 'wpdapp_update_post_meta',
                                    nonce: wpdapp_publish.nonce,
                                    post_id: wpdapp_publish.post_id,
                                    hive_data: {
                                        author: username,
                                        permlink: postData.permlink,
                                        transaction_id: response.result.id,
                                        published: true
                                    }
                                },
                                success: function(updateResponse) {
                                    if (updateResponse.success) {
                                        $status.html('<span class="wpdapp-status-ok">Successfully published to Hive! ' +
                                            '<a href="https://hive.blog/@' + username + '/' + postData.permlink + '" target="_blank">View post</a></span>');
                                    } else {
                                        $status.html('<span class="wpdapp-status-warning">Published to Hive, but failed to update post meta: ' +
                                            (updateResponse.data || 'Unknown error') + '</span>');
                                    }
                                },
                                error: function() {
                                    $status.html('<span class="wpdapp-status-warning">Published to Hive, but failed to update post meta due to server error</span>');
                                },
                                complete: function() {
                                    $button.prop('disabled', true).removeClass('loading');
                                    $button.text('Published to Hive');
                                }
                            });
                        } else {
                            $status.html('<span class="wpdapp-status-error">Keychain Error: ' + response.message + '</span>');
                            $button.prop('disabled', false).removeClass('loading');
                        }
                    }
                );
            },
            error: function() {
                $status.html('<span class="wpdapp-status-error">Server error: Could not prepare post data.</span>');
                $button.prop('disabled', false).removeClass('loading');
            }
        });
    });
}); 