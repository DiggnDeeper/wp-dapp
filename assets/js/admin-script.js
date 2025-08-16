/**
 * WP-Dapp Admin JavaScript
 */
jQuery(document).ready(function($) {
    
    console.log('WP-Dapp admin script loaded');
    
    // Counter for generating new field IDs
    var counter = 1000;
    
    // Leave Keychain detection to keychain-publish.js within the meta box context
    
    // Add beneficiary button click handler with error handling
    $(document).on('click', '#wpdapp-add-beneficiary', function(e) {
        e.preventDefault();
        
        try {
            // Generate a new index
            var index = 'new' + counter++;
            
            // Create HTML with our improved structure
            var html = 
                '<div class="wpdapp-beneficiary-row">' +
                    '<div class="wpdapp-beneficiary-inputs">' +
                        '<input type="text" name="wpdapp_beneficiaries[' + index + '][account]" placeholder="Username" value="" />' +
                        '<input type="number" name="wpdapp_beneficiaries[' + index + '][weight]" placeholder="%" value="10" min="0.01" max="100" step="0.01" />' +
                    '</div>' +
                    '<button type="button" class="button wpdapp-remove-beneficiary" onclick="wpdappRemoveBeneficiary(this); return false;" title="Remove beneficiary">' +
                        '<span class="dashicons dashicons-trash"></span>' +
                    '</button>' +
                '</div>';
            
            // Add to the table body
            $('#wpdapp-beneficiaries-container').append(html);
            
            console.log('New beneficiary row added successfully');
            
        } catch(err) {
            console.error('Error adding beneficiary row:', err);
            
            // Direct DOM method backup if jQuery fails
            try {
                var container = document.getElementById('wpdapp-beneficiaries-container');
                if (container) {
                    var div = document.createElement('div');
                    div.className = 'wpdapp-beneficiary-row';
                    div.innerHTML = 
                        '<div class="wpdapp-beneficiary-inputs">' +
                            '<input type="text" name="wpdapp_beneficiaries[backup' + counter + '][account]" placeholder="Username" value="" />' +
                            '<input type="number" name="wpdapp_beneficiaries[backup' + counter + '][weight]" placeholder="%" value="10" min="0.01" max="100" step="0.01" />' +
                        '</div>' +
                        '<button type="button" class="button wpdapp-remove-beneficiary" onclick="wpdappRemoveBeneficiary(this); return false;" title="Remove beneficiary">' +
                            '<span class="dashicons dashicons-trash"></span>' +
                        '</button>';
                    
                    container.appendChild(div);
                    console.log('Backup method: beneficiary added');
                }
            } catch(e) {
                console.error('All methods to add beneficiary failed:', e);
                alert('Could not add beneficiary. Please try refreshing the page.');
            }
        }
    });
    
    // Publish handling has been centralized in keychain-publish.js
    
    // Emergency fallback for clicks - direct DOM approach if all else fails
    document.addEventListener('click', function(e) {
        try {
            var target = e.target;
            
            // Check if we clicked a remove button or its child
            if (target.classList.contains('wpdapp-remove-beneficiary') || 
                (target.parentNode && target.parentNode.classList.contains('wpdapp-remove-beneficiary'))) {
                
                // Get the button element
                var button = target.classList.contains('wpdapp-remove-beneficiary') ? 
                            target : target.parentNode;
                
                // Find the closest beneficiary row
                var row = button;
                while (row && !row.classList.contains('wpdapp-beneficiary-row')) {
                    row = row.parentNode;
                }
                
                // Remove the row if found
                if (row && row.parentNode) {
                    row.parentNode.removeChild(row);
                    console.log('Emergency removal handler: row removed');
                    e.preventDefault();
                    e.stopPropagation();
                }
            }
        } catch(err) {
            console.error('Error in emergency handler:', err);
        }
    }, true);
}); 