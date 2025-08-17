/**
 * WP-Dapp Hive Comment Integration
 * Allows users to post replies to Hive directly from WordPress using Keychain.
 */
jQuery(document).ready(function($) {
    // Check if Keychain is available
    function isKeychainAvailable() {
        return typeof hive_keychain !== 'undefined';
    }

    // Function to generate permlink
    function generatePermlink(text) {
        let permlink = text.toLowerCase()
            .replace(/[^a-z0-9-]/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
        permlink = permlink.substring(0, 50);
        return permlink + '-' + Date.now().toString(36);
    }

    // Add reply buttons to each comment and the main comments section
    $('.wpdapp-comment-list > li').each(function() {
        const $comment = $(this);
        const hiveKey = $comment.data('hive-key'); // Assume we add this data attribute
        if (hiveKey) {
            const [author, permlink] = hiveKey.split('/');
            $comment.find('.wpdapp-comment-actions').append(
                '<button class="wpdapp-reply-button" data-author="' + author + '" data-permlink="' + permlink + '">Reply with Keychain</button>'
            );
        }
    });

    // Add main reply button at the bottom
    $('.wpdapp-hive-comments-footer').append(
        '<button class="wpdapp-reply-button" data-author="' + $('.wpdapp-hive-comments').data('root-author') + '" data-permlink="' + $('.wpdapp-hive-comments').data('root-permlink') + '">Reply to Post with Keychain</button>'
    );

    // Handle reply button click
    $(document).on('click', '.wpdapp-reply-button', function(e) {
        e.preventDefault();
        const $button = $(this);
        if (!isKeychainAvailable()) {
            alert('Hive Keychain not detected. Please install the extension.');
            return;
        }

        // Show reply form
        let $form = $button.next('.wpdapp-reply-form');
        if ($form.length === 0) {
            $form = $('<div class="wpdapp-reply-form"><textarea placeholder="Your reply..."></textarea><button class="wpdapp-submit-reply">Submit</button><button class="wpdapp-cancel-reply">Cancel</button></div>');
            $button.after($form);
        }
        $form.slideDown();
    });

    // Handle cancel
    $(document).on('click', '.wpdapp-cancel-reply', function() {
        $(this).parent().slideUp();
    });

    // Handle submit
    $(document).on('click', '.wpdapp-submit-reply', function() {
        const $form = $(this).parent();
        const content = $form.find('textarea').val().trim();
        if (!content) {
            alert('Please enter a reply.');
            return;
        }

        const $button = $form.prev('.wpdapp-reply-button');
        const parentAuthor = $button.data('author');
        const parentPermlink = $button.data('permlink');

        // Prompt for username
        const username = prompt('Enter your Hive username:');
        if (!username) return;

        const permlink = generatePermlink(content);

        const operations = [
            ['comment', {
                parent_author: parentAuthor,
                parent_permlink: parentPermlink,
                author: username,
                permlink: permlink,
                title: '',
                body: content,
                json_metadata: JSON.stringify({
                    app: 'wp-dapp/0.7.4',
                    format: 'markdown'
                })
            }]
        ];

        hive_keychain.requestBroadcast(username, operations, 'posting', function(response) {
            if (response.success) {
                alert('Reply posted successfully!');
                $form.slideUp();
                $form.find('textarea').val('');
            } else {
                alert('Error: ' + response.message);
            }
        });
    });
});
