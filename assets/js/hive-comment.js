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
    $('.wpdapp-comment-list > li.wpdapp-comment').each(function() {
        const $comment = $(this);
        const hiveKey = $comment.data('hive-key');
        if (!hiveKey) return;
        const parts = String(hiveKey).split('/');
        const author = parts[0] || '';
        const permlink = parts[1] || '';
        // Only target this LI's own actions, not descendants
        const $actions = $comment.children('.wpdapp-comment-body').children('.wpdapp-comment-actions');
        if ($actions.length && $actions.find('.wpdapp-reply-button').length === 0) {
            $actions.append('<button class="wpdapp-reply-button" aria-label="' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.replyWithKeychain : 'Reply with Keychain') + '" data-author="' + author + '" data-permlink="' + permlink + '">' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.replyWithKeychain : 'Reply with Keychain') + '</button>');
        }
    });

    // Add main reply button at the bottom
    $('.wpdapp-hive-comments-footer').append(
        '<button class="wpdapp-reply-button" aria-label="' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.replyToPostWithKeychain : 'Reply to Post with Keychain') + '" data-author="' + $('.wpdapp-hive-comments').data('root-author') + '" data-permlink="' + $('.wpdapp-hive-comments').data('root-permlink') + '">' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.replyToPostWithKeychain : 'Reply to Post with Keychain') + '</button>'
    );

    // Add global username variable
    let hiveUsername = sessionStorage.getItem('wpdapp_hive_username') || null;

    // Function to connect with Keychain
    function connectKeychain(callback) {
        if (!isKeychainAvailable()) {
            alert((wpdapp_frontend.i18n ? wpdapp_frontend.i18n.keychainNotDetected : 'Hive Keychain not detected. Please install the extension.'));
            return;
        }
        hive_keychain.requestHandshake(function(response) {
            if (response.success) {
                // Handshake doesn't provide username directly; request a sign to get it
                // For simplicity, prompt once after handshake, but ideally use a better method
                // Note: Keychain doesn't expose active account directly; we need to request a operation
                const tempUsername = prompt((wpdapp_frontend.i18n ? wpdapp_frontend.i18n.verifyPrompt : 'Enter your Hive username to verify:')); // Temporary fallback
                if (tempUsername) {
                    // Verify by requesting a sign
                    hive_keychain.requestSignBuffer(tempUsername, 'Verify WP-Dapp Connection', 'Posting', function(signResponse) {
                        if (signResponse.success) {
                            hiveUsername = tempUsername;
                            sessionStorage.setItem('wpdapp_hive_username', hiveUsername);
                            callback(hiveUsername);
                        } else {
                            alert((wpdapp_frontend.i18n ? wpdapp_frontend.i18n.verifyFailed : 'Verification failed:') + ' ' + signResponse.message);
                        }
                    });
                }
            } else {
                alert((wpdapp_frontend.i18n ? wpdapp_frontend.i18n.keychainConnectFailed : 'Keychain connection failed:') + ' ' + response.message);
            }
        });
    }

    // Update reply form to include connect button if not connected
    // In the reply button click handler:
    $(document).on('click', '.wpdapp-reply-button', function(e) {
        e.preventDefault();
        const $button = $(this);
        if (!isKeychainAvailable()) {
            alert((wpdapp_frontend.i18n ? wpdapp_frontend.i18n.keychainNotDetected : 'Hive Keychain not detected. Please install the extension.'));
            return;
        }

        let $form = $button.next('.wpdapp-reply-form');
        if ($form.length === 0) {
            $form = $('<div class="wpdapp-reply-form" role="form" aria-live="polite">' +
                (hiveUsername ? '<p>' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.connectedAs : 'Connected as:') + ' ' + hiveUsername + '</p>' : '<button class="wpdapp-connect-keychain">' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.connectWithKeychain : 'Connect with Keychain') + '</button>') +
                '<textarea placeholder="' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.yourReplyPlaceholder : 'Your reply...') + '"></textarea>' +
                '<button class="wpdapp-submit-reply">' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.submit : 'Submit') + '</button>' +
                '<button class="wpdapp-cancel-reply">' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.cancel : 'Cancel') + '</button>' +
                '</div>');
            $button.after($form);
        }
        $form.slideDown();
    });

    // Handle connect button
    $(document).on('click', '.wpdapp-connect-keychain', function() {
        connectKeychain(function(username) {
            $(this).replaceWith('<p>Connected as: ' + username + '</p>');
        }.bind(this));
    });

    // Handle cancel
    $(document).on('click', '.wpdapp-cancel-reply', function() {
        $(this).parent().slideUp();
    });

    // Handle submit
    $(document).on('click', '.wpdapp-submit-reply', function() {
        const $form = $(this).parent();
        const content = $form.find('textarea').val().trim();

        // Validation
        if (!content) {
            $form.append('<div class="wpdapp-form-error" role="status">' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.pleaseEnterReply : 'Please enter a reply.') + '</div>');
            setTimeout(() => $form.find('.wpdapp-form-error').remove(), 3000);
            return;
        }
        if (content.length < 3) {
            $form.append('<div class="wpdapp-form-error" role="status">' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.replyMinLength : 'Reply must be at least 3 characters.') + '</div>');
            setTimeout(() => $form.find('.wpdapp-form-error').remove(), 3000);
            return;
        }
        if (!hiveUsername) {
            $form.append('<div class="wpdapp-form-error" role="status">' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.pleaseConnectFirst : 'Please connect with Keychain first.') + '</div>');
            setTimeout(() => $form.find('.wpdapp-form-error').remove(), 3000);
            return;
        }

        const $button = $form.prev('.wpdapp-reply-button');
        const parentAuthor = $button.data('author');
        const parentPermlink = $button.data('permlink');

        const permlink = generatePermlink(content);

        const operations = [
            ['comment', {
                parent_author: parentAuthor,
                parent_permlink: parentPermlink,
                author: hiveUsername,
                permlink: permlink,
                title: '',
                body: content,
                json_metadata: JSON.stringify({
                    app: 'wp-dapp/0.7.4',
                    format: 'markdown'
                })
            }]
        ];

        // Add loading state
        $(this).text(wpdapp_frontend.i18n ? wpdapp_frontend.i18n.posting : 'Posting...').prop('disabled', true).addClass('loading');

        hive_keychain.requestBroadcast(hiveUsername, operations, 'posting', function(response) {
            $(this).text(wpdapp_frontend.i18n ? wpdapp_frontend.i18n.submit : 'Submit').prop('disabled', false).removeClass('loading');
            if (response.success) {
                $form.append('<div class="wpdapp-form-success" role="status">' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.postedSyncing : 'Reply posted successfully! Syncing...') + '</div>');
                // Item 2: Trigger immediate sync
                $.ajax({
                    url: wpdapp_frontend.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpdapp_sync_comments',
                        nonce: wpdapp_frontend.nonce,
                        post_id: wpdapp_frontend.post_id
                    },
                    success: function(syncResponse) {
                        if (syncResponse.success) {
                            // Fetch fresh rendered HTML and replace the block
                            $.ajax({
                                url: wpdapp_frontend.ajax_url,
                                type: 'POST',
                                data: {
                                    action: 'wpdapp_render_hive_comments',
                                    nonce: wpdapp_frontend.nonce,
                                    post_id: wpdapp_frontend.post_id
                                },
                                success: function(renderResponse) {
                                    if (renderResponse.success && renderResponse.data && renderResponse.data.html) {
                                        var $container = $('.wpdapp-hive-comments').first().parent();
                                        // Replace the entire block (comments + footer)
                                        // Find existing footer
                                        var $footer = $('.wpdapp-hive-comments-footer').first();
                                        if ($footer.length) {
                                            $footer.remove();
                                        }
                                        $('.wpdapp-hive-comments').first().replaceWith(renderResponse.data.html);
                                        $form.find('.wpdapp-form-success').text(wpdapp_frontend.i18n ? wpdapp_frontend.i18n.replyPostedSynced : 'Reply posted and synced!');
                                    } else {
                                        $form.append('<div class="wpdapp-form-error" role="status">' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.syncedRefreshFailed : 'Synced, but failed to refresh comments.') + '</div>');
                                    }
                                },
                                error: function() {
                                    $form.append('<div class="wpdapp-form-error" role="status">' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.syncedRefreshError : 'Synced, but refresh failed.') + '</div>');
                                }
                            });
                        } else {
                            $form.append('<div class="wpdapp-form-error" role="status">' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.syncFailedPrefix : 'Posted to Hive, but sync failed:') + ' ' + (syncResponse.data || 'Unknown error') + '</div>');
                        }
                    },
                    error: function() {
                        $form.append('<div class="wpdapp-form-error" role="status">' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.syncErrorOccurred : 'Posted to Hive, but sync error occurred.') + '</div>');
                    },
                    complete: function() {
                        setTimeout(() => $form.find('.wpdapp-form-success, .wpdapp-form-error').remove(), 5000);
                        $form.slideUp();
                        $form.find('textarea').val('');
                    }
                });
            } else {
                $form.append('<div class="wpdapp-form-error">Error: ' + response.message + '</div>');
                setTimeout(() => $form.find('.wpdapp-form-error').remove(), 5000);
            }
        }.bind(this));
    });
});
