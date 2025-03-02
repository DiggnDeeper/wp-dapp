/**
 * WP-Dapp Verification Script
 * 
 * Handles checking and displaying Hive publication status for posts.
 */
jQuery(document).ready(function($) {

    // Handle verification button click
    $('#wpdapp-verify-posts').on('click', function() {
        const $button = $(this);
        const $results = $('#wpdapp-verification-results');
        const $tbody = $('#wpdapp-verified-posts tbody');
        
        // Show loading state
        $button.attr('disabled', 'disabled').text(wpdappVerification.checking_text);
        $tbody.html('<tr><td colspan="4">Loading...</td></tr>');
        $results.show();
        
        // Make the AJAX request
        $.ajax({
            url: wpdappVerification.ajax_url,
            type: 'POST',
            data: {
                action: 'wpdapp_verify_posts',
                nonce: wpdappVerification.nonce
            },
            success: function(response) {
                if (!response.success) {
                    $tbody.html(`<tr><td colspan="4">${response.data || wpdappVerification.error_text}</td></tr>`);
                    return;
                }
                
                const posts = response.data;
                
                if (posts.length === 0) {
                    $tbody.html(`<tr><td colspan="4">${wpdappVerification.no_posts_text}</td></tr>`);
                    return;
                }
                
                // Clear existing rows
                $tbody.empty();
                
                // Add a row for each post
                $.each(posts, function(index, post) {
                    let statusText = '';
                    let hiveLink = '';
                    
                    if (post.hive_published) {
                        statusText = '<span style="color:green;font-weight:bold;">Published</span>';
                        hiveLink = `<a href="https://hive.blog/@${post.hive_author}/${post.hive_permlink}" target="_blank">View on Hive</a>`;
                    } else if (post.hive_publish_error) {
                        statusText = `<span style="color:red;font-weight:bold;">Error</span><br><small>${post.hive_publish_error}</small>`;
                    } else {
                        statusText = '<span style="color:orange;font-weight:bold;">Not Published</span>';
                    }
                    
                    $tbody.append(`
                        <tr>
                            <td>${post.ID}</td>
                            <td><a href="${post.edit_url}">${post.title}</a></td>
                            <td>${statusText}</td>
                            <td>${hiveLink}</td>
                        </tr>
                    `);
                });
            },
            error: function() {
                $tbody.html(`<tr><td colspan="4">${wpdappVerification.error_text}</td></tr>`);
            },
            complete: function() {
                // Reset button
                $button.removeAttr('disabled').text('Check Hive Publication Status');
            }
        });
    });

}); 