/**
 * WP-Dapp Admin JavaScript
 */
jQuery(document).ready(function($) {
    
    console.log('WP-Dapp admin script loaded');
    
    // Counter for generating new field IDs
    var counter = 1000;
    
    // Add beneficiary button click handler
    $('#wpdapp-add-beneficiary').on('click', function() {
        // Generate a new index
        var index = 'new' + counter++;
        
        // Create HTML with direct inline onclick handler 
        var html = 
            '<div class="wpdapp-beneficiary-row">' +
                '<input type="text" name="wpdapp_beneficiaries[' + index + '][account]" placeholder="Hive Username" value="" />' +
                '<input type="number" name="wpdapp_beneficiaries[' + index + '][weight]" placeholder="Weight (%)" value="10" min="0.01" max="100" step="0.01" />' +
                '<button type="button" class="button wpdapp-remove-beneficiary" onclick="wpdappRemoveBeneficiary(this); return false;">' +
                    '<span class="dashicons dashicons-trash"></span>' +
                '</button>' +
            '</div>';
        
        // Append to the container
        $('#wpdapp-beneficiaries-container').append(html);
        
        console.log('New beneficiary row added');
    });
    
    // Emergency fallback - direct DOM approach if all else fails
    document.addEventListener('click', function(e) {
        var target = e.target;
        
        // Check if we clicked a remove button or its child
        if (target.classList.contains('wpdapp-remove-beneficiary') || 
            (target.parentNode && target.parentNode.classList.contains('wpdapp-remove-beneficiary'))) {
            
            // Get the button element
            var button = target.classList.contains('wpdapp-remove-beneficiary') ? 
                         target : target.parentNode;
                
            // Get the parent row
            var row = button.parentNode;
            if (row && row.classList.contains('wpdapp-beneficiary-row')) {
                // Remove the row
                row.parentNode.removeChild(row);
                console.log('Emergency removal handler triggered');
                e.preventDefault();
                e.stopPropagation();
            }
        }
    }, true);
    
}); 