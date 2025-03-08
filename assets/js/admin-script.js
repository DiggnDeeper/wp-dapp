/**
 * WP-Dapp Admin JavaScript
 */
jQuery(document).ready(function($) {
    
    console.log('WP-Dapp admin script loaded');
    
    // Handle adding beneficiaries
    $(document).on('click', '.wpdapp-add-beneficiary', function(e) {
        e.preventDefault();
        console.log('Add beneficiary button clicked');
        
        // Get the template
        var template = $('#beneficiary-template').html();
        console.log('Template found:', template ? 'Yes' : 'No');
        
        if (!template) {
            console.error('Beneficiary template not found');
            return;
        }
        
        // Generate a unique index
        var index = new Date().getTime();
        template = template.replace(/INDEX/g, index);
        
        // Add to the table body
        $('.wpdapp-beneficiaries-table tbody').append(template);
        console.log('Added new beneficiary row');

        // Remove the "No beneficiaries added" row if it exists
        $('#wpdapp-no-beneficiaries').remove();
    });
    
    // Handle removing beneficiaries
    $(document).on('click', '.wpdapp-remove-beneficiary', function(e) {
        e.preventDefault();
        console.log('Remove beneficiary button clicked');
        $(this).closest('tr').remove();
        
        // If no beneficiaries left, add the "No beneficiaries" message back
        if ($('.wpdapp-beneficiaries-table tbody tr').length === 0) {
            $('.wpdapp-beneficiaries-table tbody').append('<tr id="wpdapp-no-beneficiaries"><td colspan="3">No beneficiaries added</td></tr>');
        }
    });
    
    // Debug helper
    console.log('Beneficiary elements on page:');
    console.log('- Add button:', $('.wpdapp-add-beneficiary').length);
    console.log('- Template:', $('#beneficiary-template').length);
    console.log('- Table:', $('.wpdapp-beneficiaries-table').length);
}); 