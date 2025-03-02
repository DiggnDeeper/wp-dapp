/**
 * WP-Dapp Admin JavaScript
 */
jQuery(document).ready(function($) {
    
    // Handle adding beneficiaries
    $('.wpdapp-meta-box').on('click', '.add-beneficiary', function(e) {
        e.preventDefault();
        
        // Get the template
        var template = $('.beneficiary-template').html();
        
        // Generate a unique index
        var index = new Date().getTime();
        template = template.replace(/INDEX/g, index);
        
        // Add to the list
        $('.beneficiary-list').append(template);
    });
    
    // Handle removing beneficiaries
    $('.wpdapp-meta-box').on('click', '.remove-beneficiary', function(e) {
        e.preventDefault();
        $(this).closest('.beneficiary-item').remove();
    });
    
}); 