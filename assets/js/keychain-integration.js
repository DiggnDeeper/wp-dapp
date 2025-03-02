/**
 * WP-Dapp Hive Keychain Integration
 * Handles interaction with the Hive Keychain browser extension
 */
jQuery(document).ready(function($) {
    
    // Check if Keychain is available
    const isKeychainAvailable = () => {
        return typeof hive_keychain !== 'undefined';
    };
    
    // Show or hide Keychain status message
    const updateKeychainStatus = () => {
        const $status = $('#wpdapp-keychain-status');
        
        if (isKeychainAvailable()) {
            $status.html('<span class="wpdapp-status-ok">✓ Hive Keychain detected</span>');
        } else {
            $status.html('<span class="wpdapp-status-error">✗ Hive Keychain not detected</span>' +
                '<p class="description">Please install the <a href="https://hive-keychain.com/" target="_blank">Hive Keychain browser extension</a> to use this plugin.</p>');
        }
    };
    
    // Initialize
    updateKeychainStatus();
    
    // Handle account verification button click
    $('#wpdapp-verify-account').on('click', function() {
        const $button = $(this);
        const $status = $('#wpdapp-credential-status');
        const account = $('#hive_account').val();
        
        // Basic validation
        if (!account) {
            $status.html('<span class="wpdapp-status-error">Please enter your Hive account name</span>');
            return;
        }
        
        // Check if Keychain is available
        if (!isKeychainAvailable()) {
            $status.html('<span class="wpdapp-status-error">Hive Keychain extension not detected</span>');
            return;
        }
        
        // Set button state to loading
        $button.prop('disabled', true).text(wpdapp_settings.verifying_text);
        $status.html('<span class="wpdapp-status-pending">Verifying...</span>');
        
        // Request a simple verification from Keychain
        // This will prompt the user to approve a test message signing
        hive_keychain.requestSignBuffer(
            account, 
            'Verify WP-Dapp Plugin Connection', 
            'Posting', 
            response => {
                if (response.success) {
                    // Verify the signature with the server
                    $.ajax({
                        url: wpdapp_settings.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'wpdapp_verify_keychain',
                            nonce: wpdapp_settings.nonce,
                            account: account,
                            message: 'Verify WP-Dapp Plugin Connection',
                            signature: response.result
                        },
                        success: function(serverResponse) {
                            if (serverResponse.success) {
                                $status.html('<span class="wpdapp-status-ok">' + wpdapp_settings.success_text + '</span>');
                            } else {
                                $status.html('<span class="wpdapp-status-error">' + (serverResponse.data || wpdapp_settings.error_text) + '</span>');
                            }
                        },
                        error: function() {
                            $status.html('<span class="wpdapp-status-error">Error: Could not connect to server</span>');
                        },
                        complete: function() {
                            // Reset button state
                            $button.prop('disabled', false).text(wpdapp_settings.verify_text);
                        }
                    });
                } else {
                    $status.html('<span class="wpdapp-status-error">' + response.message + '</span>');
                    $button.prop('disabled', false).text(wpdapp_settings.verify_text);
                }
            }
        );
    });
}); 