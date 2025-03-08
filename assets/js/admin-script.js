/**
 * WP-Dapp Admin JavaScript
 */
jQuery(document).ready(function($) {
    
    // Log when script is loaded to verify it's working
    console.log('WP-Dapp admin script loaded');
    
    // Counter to generate unique IDs for dynamic rows
    var beneficiaryCounter = 1000;
    
    /**
     * Remove a beneficiary row by ID
     * @param {string} rowId - The ID of the row to remove
     */
    function removeBeneficiaryRow(rowId) {
        console.log('Removing beneficiary row:', rowId);
        
        // Get the row element
        var $row = $('#' + rowId);
        
        if (!$row.length) {
            console.error('Row not found:', rowId);
            return;
        }
        
        // Add a visual indicator that the row is being removed
        $row.css('background-color', '#ffecec').fadeOut(300, function() {
            // Remove the row from the DOM
            $(this).remove();
            
            // Update the No Beneficiaries message
            updateNobeneficiariesMessage();
        });
    }
    
    /**
     * Update the No Beneficiaries message based on table state
     */
    function updateNobeneficiariesMessage() {
        console.log('Updating no beneficiaries message');
        
        // Get the number of visible beneficiary rows
        var $tbody = $('.wpdapp-beneficiaries-table tbody');
        var visibleRows = $tbody.find('tr').not('#wpdapp-no-beneficiaries').length;
        
        console.log('Visible beneficiary rows:', visibleRows);
        
        if (visibleRows === 0) {
            // If no rows, show the message
            if ($('#wpdapp-no-beneficiaries').length === 0) {
                $tbody.html('<tr id="wpdapp-no-beneficiaries"><td colspan="3">No beneficiaries added</td></tr>');
            }
        } else {
            // If rows exist, hide the message
            $('#wpdapp-no-beneficiaries').remove();
        }
    }
    
    // Run the update function on page load
    updateNobeneficiariesMessage();
    
    // Handle Add Beneficiary button click
    $('.wpdapp-add-beneficiary').on('click', function(e) {
        e.preventDefault();
        
        console.log('Add beneficiary button clicked');
        
        // Get the template HTML
        var template = $('#beneficiary-template').html();
        
        if (!template) {
            console.error('Beneficiary template not found');
            return;
        }
        
        // Generate a unique ID
        var uniqueId = 'new-' + (beneficiaryCounter++);
        
        // Replace INDEX with the unique ID
        template = template.replace(/INDEX/g, uniqueId);
        
        // Add the new row to the table
        $('.wpdapp-beneficiaries-table tbody').append(template);
        
        // Update the message
        updateNobeneficiariesMessage();
        
        // Add highlight effect to show the new row
        $('#beneficiary-row-' + uniqueId).css('background-color', '#ecffec').animate({
            backgroundColor: 'transparent'
        }, 1000);
    });
    
    // Use event delegation to handle Remove button clicks
    $(document).on('click', '.wpdapp-remove-beneficiary', function(e) {
        e.preventDefault();
        
        // Log the click event
        console.log('Remove beneficiary button clicked');
        
        // Get the row ID from the data attribute
        var rowId = $(this).data('row-id');
        
        if (!rowId) {
            // Fallback to finding the closest tr as a last resort
            console.log('No row ID found in data attribute, using closest tr');
            var $row = $(this).closest('tr');
            if ($row.length) {
                $row.css('background-color', '#ffecec').fadeOut(300, function() {
                    $(this).remove();
                    updateNobeneficiariesMessage();
                });
                return;
            }
            
            console.error('Could not find row to remove');
            return;
        }
        
        // Remove the row using its ID
        removeBeneficiaryRow(rowId);
    });
    
    // Debug: Log counts of key elements
    console.log('Beneficiary elements found:');
    console.log('- Add button:', $('.wpdapp-add-beneficiary').length);
    console.log('- Remove buttons:', $('.wpdapp-remove-beneficiary').length);
    console.log('- Template element:', $('#beneficiary-template').length);
}); 