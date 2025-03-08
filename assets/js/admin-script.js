/**
 * WP-Dapp Admin JavaScript
 */
jQuery(document).ready(function($) {
    
    console.log('WP-Dapp admin script loaded');
    
    // Counter for generating new field IDs
    var counter = 1000;
    
    // Handle removing a beneficiary row
    $('#wpdapp-beneficiaries-container').on('click', '.wpdapp-remove-beneficiary', function() {
        // Simple direct removal of the parent row
        $(this).parent('.wpdapp-beneficiary-row').remove();
        
        console.log('Beneficiary row removed');
    });
    
    // Handle adding a new beneficiary row
    $('#wpdapp-add-beneficiary').on('click', function() {
        // Generate a new index
        var index = 'new' + counter++;
        
        // Create a simple HTML structure
        var html = 
            '<div class="wpdapp-beneficiary-row">' +
                '<input type="text" name="wpdapp_beneficiaries[' + index + '][account]" placeholder="Hive Username" value="" />' +
                '<input type="number" name="wpdapp_beneficiaries[' + index + '][weight]" placeholder="Weight (%)" value="10" min="0.01" max="100" step="0.01" />' +
                '<button type="button" class="button wpdapp-remove-beneficiary"><span class="dashicons dashicons-trash"></span></button>' +
            '</div>';
        
        // Append to the container
        $('#wpdapp-beneficiaries-container').append(html);
        
        console.log('New beneficiary row added');
    });
    
}); 