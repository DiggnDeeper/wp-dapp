/**
 * WP-Dapp Admin JavaScript
 */
jQuery(document).ready(function($) {
    
    console.log('WP-Dapp admin script loaded');
    
    // Function to check if we need to show or hide the "No beneficiaries" message
    function updateNoBeneficiariesMessage() {
        var visibleRows = $('.wpdapp-beneficiaries-table tbody tr').not('#wpdapp-no-beneficiaries').length;
        
        if (visibleRows === 0) {
            // Show the message if no rows exist
            if ($('#wpdapp-no-beneficiaries').length === 0) {
                $('.wpdapp-beneficiaries-table tbody').append(
                    '<tr id="wpdapp-no-beneficiaries"><td colspan="3">No beneficiaries added</td></tr>'
                );
            }
        } else {
            // Hide the message if rows exist
            $('#wpdapp-no-beneficiaries').remove();
        }
    }
    
    // Initial check
    updateNoBeneficiariesMessage();
    
    // Handle adding beneficiaries
    $(document).on('click', '.wpdapp-add-beneficiary', function(e) {
        e.preventDefault();
        console.log('Add beneficiary button clicked');
        
        // Get the template
        var template = $('#beneficiary-template').html();
        
        if (!template) {
            console.error('Beneficiary template not found');
            return;
        }
        
        // Generate a unique index
        var index = new Date().getTime();
        template = template.replace(/INDEX/g, index);
        
        // Add to the table body
        $('.wpdapp-beneficiaries-table tbody').append(template);
        
        // Update the message
        updateNoBeneficiariesMessage();
    });
    
    // Add a global callback to handle when any beneficiary row is removed
    // This uses event delegation via the document to catch all removals
    $(document).on('click', '.wpdapp-remove-beneficiary', function() {
        // The row is already removed by the inline onclick handler
        // We just need to update the message after a short delay
        setTimeout(updateNoBeneficiariesMessage, 100);
    });
    
    // Debug helper
    console.log('Beneficiary elements on page:');
    console.log('- Add button:', $('.wpdapp-add-beneficiary').length);
    console.log('- Template:', $('#beneficiary-template').length);
    console.log('- Table:', $('.wpdapp-beneficiaries-table').length);
    console.log('- Existing rows:', $('.wpdapp-beneficiaries-table tbody tr').not('#wpdapp-no-beneficiaries').length);
}); 