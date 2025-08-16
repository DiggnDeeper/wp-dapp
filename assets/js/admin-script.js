/**
 * WP-Dapp Admin JavaScript
 */
jQuery(document).ready(function($) {
    
    console.log('WP-Dapp admin script loaded');
    
    // Counter for generating new field IDs
    var counter = 1000;
    
    // Leave Keychain detection to keychain-publish.js within the meta box context
    
    // Add beneficiary button click handler with error handling
    $(document).on('click', '#wpdapp-add-beneficiary', function(e) {
        e.preventDefault();
        
        try {
            // Generate a new index
            var index = 'new' + counter++;
            
            // Create HTML with our improved structure
            var html = 
                '<div class="wpdapp-beneficiary-row">' +
                    '<div class="wpdapp-beneficiary-inputs">' +
                        '<input type="text" name="wpdapp_beneficiaries[' + index + '][account]" placeholder="Username" value="" />' +
                        '<input type="number" name="wpdapp_beneficiaries[' + index + '][weight]" placeholder="%" value="10" min="0.01" max="100" step="0.01" />' +
                    '</div>' +
                    '<button type="button" class="button wpdapp-remove-beneficiary" onclick="wpdappRemoveBeneficiary(this); return false;" title="Remove beneficiary">' +
                        '<span class="dashicons dashicons-trash"></span>' +
                    '</button>' +
                '</div>';
            
            // Add to the table body
            $('#wpdapp-beneficiaries-container').append(html);
            
            console.log('New beneficiary row added successfully');
            
        } catch(err) {
            console.error('Error adding beneficiary row:', err);
            
            // Direct DOM method backup if jQuery fails
            try {
                var container = document.getElementById('wpdapp-beneficiaries-container');
                if (container) {
                    var div = document.createElement('div');
                    div.className = 'wpdapp-beneficiary-row';
                    div.innerHTML = 
                        '<div class="wpdapp-beneficiary-inputs">' +
                            '<input type="text" name="wpdapp_beneficiaries[backup' + counter + '][account]" placeholder="Username" value="" />' +
                            '<input type="number" name="wpdapp_beneficiaries[backup' + counter + '][weight]" placeholder="%" value="10" min="0.01" max="100" step="0.01" />' +
                        '</div>' +
                        '<button type="button" class="button wpdapp-remove-beneficiary" onclick="wpdappRemoveBeneficiary(this); return false;" title="Remove beneficiary">' +
                            '<span class="dashicons dashicons-trash"></span>' +
                        '</button>';
                    
                    container.appendChild(div);
                    console.log('Backup method: beneficiary added');
                }
            } catch(e) {
                console.error('All methods to add beneficiary failed:', e);
                alert('Could not add beneficiary. Please try refreshing the page.');
            }
        }
    });
    
    // Handle Hive publishing with Keychain
    $(document).on('click', '#wpdapp-publish-button', function() {
        // Don't proceed if button is disabled
        if ($(this).attr('disabled')) {
            return;
        }
        
        var statusElem = $('#wpdapp-publish-status');
        statusElem.html('<p><span class="dashicons dashicons-update"></span> Preparing post data...</p>');
        
        // Disable button while processing
        $(this).attr('disabled', 'disabled');
        
        // Call AJAX to prepare post data
        $.ajax({
            type: 'POST',
            url: wpdapp_publish.ajax_url,
            data: {
                action: 'wpdapp_prepare_post',
                post_id: wpdapp_publish.post_id,
                nonce: wpdapp_publish.nonce
            },
            success: function(response) {
                if (response.success) {
                    statusElem.html('<p><span class="dashicons dashicons-update"></span> Publishing to Hive...</p>');
                    publishToHive(response.data);
                } else {
                    handleError(response.data);
                }
            },
            error: function() {
                handleError('Failed to prepare post data');
            }
        });
    });
    
    // Function to publish to Hive using Keychain
    function publishToHive(data) {
        if (typeof window.hive_keychain === 'undefined') {
            handleError('Hive Keychain not detected');
            return;
        }
        
        // Build operations array
        var operations = [
            ['comment', {
                parent_author: '',
                parent_permlink: data.parent_permlink,
                author: wpdapp_publish.hive_account,
                permlink: data.permlink,
                title: data.title,
                body: data.body,
                json_metadata: data.json_metadata
            }]
        ];
        
        // Add beneficiaries operation if present
        if (data.beneficiaries && data.beneficiaries.length > 0) {
            operations.push(['comment_options', {
                author: wpdapp_publish.hive_account,
                permlink: data.permlink,
                max_accepted_payout: '1000000.000 HBD',
                percent_hbd: 10000,
                allow_votes: true,
                allow_curation_rewards: true,
                extensions: [
                    [0, {
                        beneficiaries: data.beneficiaries
                    }]
                ]
            }]);
        }
        
        // Use Keychain to broadcast operations
        window.hive_keychain.requestBroadcast(
            wpdapp_publish.hive_account,
            operations,
            'posting',
            function(response) {
                if (response.success) {
                    // Post published successfully, update post meta
                    updatePostMeta(data.permlink);
                } else {
                    handleError('Keychain Error: ' + response.message);
                }
            }
        );
    }
    
    // Function to update post meta after Hive publication
    function updatePostMeta(permlink) {
        $.ajax({
            type: 'POST',
            url: wpdapp_publish.ajax_url,
            data: {
                action: 'wpdapp_update_post_meta',
                post_id: wpdapp_publish.post_id,
                hive_author: wpdapp_publish.hive_account,
                hive_permlink: permlink,
                nonce: wpdapp_publish.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#wpdapp-publish-status').html('<p class="wpdapp-status-ok"><span class="dashicons dashicons-yes"></span> ' + 
                        'Published to Hive successfully!</p><p><a href="https://hive.blog/@' + 
                        wpdapp_publish.hive_account + '/' + permlink + '" target="_blank">View on Hive</a></p>');
                    
                    // Reload page after short delay to show updated meta box
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    handleError('Error updating post meta: ' + response.data);
                }
            },
            error: function() {
                handleError('Failed to update post meta');
            }
        });
    }
    
    // Function to handle errors
    function handleError(message) {
        $('#wpdapp-publish-status').html('<p class="wpdapp-status-error"><span class="dashicons dashicons-no"></span> ' + message + '</p>');
        $('#wpdapp-publish-button').removeAttr('disabled');
    }
    
    // Emergency fallback for clicks - direct DOM approach if all else fails
    document.addEventListener('click', function(e) {
        try {
            var target = e.target;
            
            // Check if we clicked a remove button or its child
            if (target.classList.contains('wpdapp-remove-beneficiary') || 
                (target.parentNode && target.parentNode.classList.contains('wpdapp-remove-beneficiary'))) {
                
                // Get the button element
                var button = target.classList.contains('wpdapp-remove-beneficiary') ? 
                            target : target.parentNode;
                
                // Find the closest beneficiary row
                var row = button;
                while (row && !row.classList.contains('wpdapp-beneficiary-row')) {
                    row = row.parentNode;
                }
                
                // Remove the row if found
                if (row && row.parentNode) {
                    row.parentNode.removeChild(row);
                    console.log('Emergency removal handler: row removed');
                    e.preventDefault();
                    e.stopPropagation();
                }
            }
        } catch(err) {
            console.error('Error in emergency handler:', err);
        }
    }, true);
}); 