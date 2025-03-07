/**
 * WP-Dapp Admin JavaScript
 */
jQuery(document).ready(function($) {
    
    // Handle adding beneficiaries
    $('#wpdapp-hive-publish').on('click', '.wpdapp-add-beneficiary', function(e) {
        e.preventDefault();
        
        // Get the template
        var template = $('.beneficiary-template').html();
        
        // Generate a unique index
        var index = new Date().getTime();
        template = template.replace(/INDEX/g, index);
        
        // Add to the table body
        $('.wpdapp-beneficiaries-table tbody').append(template);

        // Remove the "No beneficiaries added" row if it exists
        $('#wpdapp-no-beneficiaries').remove();
    });
    
    // Handle removing beneficiaries
    $('#wpdapp-hive-publish').on('click', '.wpdapp-remove-beneficiary', function(e) {
        e.preventDefault();
        $(this).closest('tr').remove();
        
        // If no beneficiaries left, add the "No beneficiaries" message back
        if ($('.wpdapp-beneficiaries-table tbody tr').length === 0) {
            $('.wpdapp-beneficiaries-table tbody').append('<tr id="wpdapp-no-beneficiaries"><td colspan="3">No beneficiaries added</td></tr>');
        }
    });
    
}); 