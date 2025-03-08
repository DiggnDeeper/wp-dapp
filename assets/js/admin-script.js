/**
 * WP-Dapp Admin JavaScript
 */
jQuery(document).ready(function($) {
    
    console.log('WP-Dapp admin script loaded');
    
    // Function to update the beneficiaries UI state
    function updateBeneficiariesUI() {
        console.log('Updating beneficiaries UI');
        
        // Count visible rows (excluding the no-beneficiaries message row)
        var visibleRows = $('.wpdapp-beneficiaries-table tbody tr').not('#wpdapp-no-beneficiaries').length;
        console.log('Visible beneficiary rows:', visibleRows);
        
        // If no visible rows, show the no-beneficiaries message
        if (visibleRows === 0) {
            // Only add if not already present
            if ($('#wpdapp-no-beneficiaries').length === 0) {
                $('.wpdapp-beneficiaries-table tbody').append('<tr id="wpdapp-no-beneficiaries"><td colspan="3">No beneficiaries added</td></tr>');
            }
        } else {
            // If we have rows, make sure the message is gone
            $('#wpdapp-no-beneficiaries').remove();
        }
    }
    
    // Initial UI update
    updateBeneficiariesUI();
    
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

        // Update UI
        updateBeneficiariesUI();
    });
    
    // Handle removing beneficiaries - both existing and dynamically added
    $(document).on('click', '.wpdapp-remove-beneficiary', function(e) {
        e.preventDefault();
        console.log('Remove beneficiary button clicked');
        
        // Find the row and remove it
        var $row = $(this).closest('tr');
        $row.fadeOut(300, function() {
            $(this).remove();
            // Update UI after animation completes
            updateBeneficiariesUI();
        });
    });
    
    // Debug helper
    console.log('Beneficiary elements on page:');
    console.log('- Add button:', $('.wpdapp-add-beneficiary').length);
    console.log('- Template:', $('#beneficiary-template').length);
    console.log('- Table:', $('.wpdapp-beneficiaries-table').length);
    console.log('- Existing rows:', $('.wpdapp-beneficiaries-table tbody tr').not('#wpdapp-no-beneficiaries').length);
}); 