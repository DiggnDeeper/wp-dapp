/**
 * WP-Dapp Hive Keychain Integration
 * Handles interaction with the Hive Keychain browser extension
 */
jQuery(document).ready(function($) {
    
    // Check if Keychain is available
    const isKeychainAvailable = () => {
        return typeof hive_keychain !== 'undefined' && hive_keychain;
    };
    
    // Show or hide Keychain status message
    const updateKeychainStatus = () => {
        const $status = $('#wpdapp-keychain-detection');
        
        if (isKeychainAvailable()) {
            $status.html('<span class="wpdapp-status-ok"><span class="dashicons dashicons-yes"></span> Hive Keychain detected</span>');
            $('#wpdapp-verify-button').prop('disabled', false);
        } else {
            $status.html('<span class="wpdapp-status-error"><span class="dashicons dashicons-no"></span> Hive Keychain not detected</span>' +
                '<br><small>Please install the <a href="https://hive-keychain.com/" target="_blank">Hive Keychain browser extension</a> to use this plugin.</small>');
            $('#wpdapp-verify-button').prop('disabled', true);
        }
    };
    
    // Initialize
    updateKeychainStatus();
    
    // Check periodically for Keychain - sometimes extensions load after page is ready
    let checkCount = 0;
    const maxChecks = 10; // Check up to 10 times (5 seconds)
    
    const periodicCheck = setInterval(function() {
        if (isKeychainAvailable()) {
            updateKeychainStatus();
            clearInterval(periodicCheck);
        } else if (++checkCount >= maxChecks) {
            clearInterval(periodicCheck);
        }
    }, 500); // Check every 500ms
    
    // Handle account verification button click
    $('#wpdapp-verify-button').on('click', function() {
        const $button = $(this);
        const $status = $('#wpdapp-verify-status');
        const account = $('#hive_account').val();
        
        // Basic validation
        if (!account) {
            $status.html('<div class="wpdapp-status-error"><span class="dashicons dashicons-warning"></span> Please enter your Hive account name</div>');
            return;
        }
        
        // Check if Keychain is available
        if (!isKeychainAvailable()) {
            // Try to detect one more time in case it's just loaded
            setTimeout(function() {
                if (isKeychainAvailable()) {
                    updateKeychainStatus();
                    $status.html('<div class="wpdapp-status-ok"><span class="dashicons dashicons-yes"></span> Keychain detected. Please try again.</div>');
                } else {
                    $status.html('<div class="wpdapp-status-error"><span class="dashicons dashicons-no"></span> Hive Keychain extension not detected</div>');
                }
            }, 100);
            return;
        }
        
        // Set button state to loading
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> ' + wpdapp_settings.verifying_text);
        $status.html('<div class="wpdapp-status-checking"><span class="dashicons dashicons-update"></span> Verifying...</div>');
        
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
                                $status.html('<div class="wpdapp-status-ok"><span class="dashicons dashicons-yes"></span> ' + wpdapp_settings.success_text + '</div>');
                            } else {
                                $status.html('<div class="wpdapp-status-error"><span class="dashicons dashicons-no"></span> ' + (serverResponse.data || wpdapp_settings.error_text) + '</div>');
                            }
                        },
                        error: function() {
                            $status.html('<div class="wpdapp-status-error"><span class="dashicons dashicons-no"></span> Error: Could not connect to server</div>');
                        },
                        complete: function() {
                            // Reset button state
                            $button.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> ' + wpdapp_settings.verify_text);
                        }
                    });
                } else {
                    $status.html('<div class="wpdapp-status-error"><span class="dashicons dashicons-no"></span> ' + response.message + '</div>');
                    $button.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> ' + wpdapp_settings.verify_text);
                }
            }
        );
    });
}); 