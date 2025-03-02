/**
 * WP-Dapp Admin Settings JavaScript
 */
jQuery(document).ready(function($) {
    
    // Handle credential verification
    $('#wpdapp-verify-credentials').on('click', function() {
        var $button = $(this);
        var $status = $('#wpdapp-credential-status');
        
        // Get account and key values
        var account = $('#hive_account').val();
        var key = $('#private_key').val();
        var secureStorage = $('#secure_storage').is(':checked');
        
        // Basic validation
        if (!account || (!key && !secureStorage)) {
            $status.html('<span style="color: #dc3232;">Please enter your Hive account and private key</span>');
            return;
        }
        
        // Set button state to loading
        $button.prop('disabled', true).text(wpdapp_settings.verifying_text);
        $status.html('<span style="color: #999;">Verifying...</span>');
        
        // Make AJAX request to verify credentials
        $.ajax({
            url: wpdapp_settings.ajax_url,
            type: 'POST',
            data: {
                action: 'wpdapp_verify_credentials',
                nonce: wpdapp_settings.nonce,
                account: account,
                key: key,
                secure_storage: secureStorage ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    $status.html('<span style="color: #46b450;">' + wpdapp_settings.success_text + '</span>');
                } else {
                    $status.html('<span style="color: #dc3232;">' + (response.data || wpdapp_settings.error_text) + '</span>');
                }
            },
            error: function() {
                $status.html('<span style="color: #dc3232;">Error: Could not connect to server</span>');
            },
            complete: function() {
                // Reset button state
                $button.prop('disabled', false).text(wpdapp_settings.verify_text);
            }
        });
    });
    
    // Toggle key field visibility based on secure storage setting
    $('#secure_storage').on('change', function() {
        if ($(this).is(':checked')) {
            $('.wpdapp-secure-key-notice').show();
        } else {
            $('.wpdapp-secure-key-notice').hide();
        }
    });
    
}); 