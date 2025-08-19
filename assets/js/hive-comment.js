/**
 * WP-Dapp Hive Comment Integration
 * Allows users to post replies to Hive directly from WordPress using Keychain.
 */
jQuery(document).ready(function($) {
    // Check if Keychain is available
    function isKeychainAvailable() {
        return typeof hive_keychain !== 'undefined';
    }

    // Counter to generate unique IDs per reply form
    let wpdappFormCounter = 0;

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
    if (wpdapp_frontend.show_reply_buttons) {
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
            $actions.append('<button type="button" class="wpdapp-reply-button" aria-label="' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.replyWithKeychain : 'Reply with Keychain') + '" data-author="' + author + '" data-permlink="' + permlink + '">' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.replyWithKeychain : 'Reply with Keychain') + '</button>');
        }
      });
    }

    // Add main reply button at the bottom
    if (wpdapp_frontend.show_reply_buttons) {
      $('.wpdapp-hive-comments-footer').append(
          '<button type="button" class="wpdapp-reply-button" aria-label="' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.replyToPostWithKeychain : 'Reply to Post with Keychain') + '" data-author="' + $('.wpdapp-hive-comments').data('root-author') + '" data-permlink="' + $('.wpdapp-hive-comments').data('root-permlink') + '">' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.replyToPostWithKeychain : 'Reply to Post with Keychain') + '</button>'
      );
    }

    // Username persistence: prefer localStorage, fallback to sessionStorage
    let hiveUsername = (function() {
        try { return localStorage.getItem('wpdapp_hive_username') || sessionStorage.getItem('wpdapp_hive_username') || null; } catch(e) { return sessionStorage.getItem('wpdapp_hive_username') || null; }
    })();

    // Open reply form (no connect step)
    $(document).on('click', '.wpdapp-reply-button', function(e) {
        e.preventDefault();
        const $button = $(this);

        let $form = $button.next('.wpdapp-reply-form');
        if ($form.length === 0) {
            const formId = ++wpdappFormCounter;
            const usernameInputId = 'wpdapp-username-' + formId;
            const connected = !!hiveUsername;
            $form = $('<div class="wpdapp-reply-form" role="form" aria-live="polite">' +
                '<div class="wpdapp-conn-row">' +
                    '<span class="wpdapp-status-chip ' + (connected ? 'connected' : 'not-connected') + '">' + (connected ? (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.statusConnected : 'Connected') : (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.statusNotConnected : 'Not connected')) + '</span>' +
                    (connected
                        ? '<p class="wpdapp-connected-as">' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.connectedAs : 'Connected as:') + ' ' + hiveUsername + '</p>'
                        : '<label class="wpdapp-username-label" for="' + usernameInputId + '">' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.enterHiveUsername : 'Enter your Hive username:') + '</label>' +
                          '<input type="text" class="wpdapp-username" id="' + usernameInputId + '" placeholder="' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.hiveUsernamePlaceholder : 'Hive username') + '">'
                      ) +
                '</div>' +
                '<textarea aria-label="' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.yourReply : 'Your reply') + '" placeholder="' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.yourReplyPlaceholder : 'Your reply...') + '"></textarea>' +
                '<button type="button" class="wpdapp-submit-reply">' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.submit : 'Submit') + '</button>' +
                '<button type="button" class="wpdapp-cancel-reply">' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.cancel : 'Cancel') + '</button>' +
                '</div>');
            $button.after($form);
        }
        $form.slideDown();
        $form.find('textarea').focus();

        // If Keychain missing, inform inline but do not block
        if (!isKeychainAvailable() && $form.find('.wpdapp-keychain-warning').length === 0) {
            $form.prepend('<div class="wpdapp-form-error wpdapp-keychain-warning" role="status">' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.keychainNotDetected : 'Hive Keychain not detected. Please install the extension.') + '</div>');
        }
    });

    // Cancel
    $(document).on('click', '.wpdapp-cancel-reply', function() {
        $(this).parent().slideUp();
    });

    // Submit
    let isSyncInFlight = false;

    $(document).on('click', '.wpdapp-submit-reply', function() {
        const $form = $(this).parent();
        const content = $form.find('textarea').val().trim();

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
        if (content.length > 5000) {
            $form.append('<div class="wpdapp-form-error" role="status">' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.replyMaxLength : 'Reply must be at most 5000 characters.') + '</div>');
            setTimeout(() => $form.find('.wpdapp-form-error').remove(), 3000);
            return;
        }

        // Determine username: stored or typed
        let usedUsername = hiveUsername;
        if (!usedUsername) {
            const typed = $form.find('.wpdapp-username').val();
            if (typed && typed.trim().length > 0) {
                usedUsername = typed.trim();
            } else {
                $form.append('<div class="wpdapp-form-error" role="status">' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.pleaseEnterUsername : 'Please enter your Hive username.') + '</div>');
                setTimeout(() => $form.find('.wpdapp-form-error').remove(), 3000);
                return;
            }
        }

        if (!isKeychainAvailable()) {
            alert((wpdapp_frontend.i18n ? wpdapp_frontend.i18n.keychainNotDetected : 'Hive Keychain not detected. Please install the extension.'));
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
                author: usedUsername,
                permlink: permlink,
                title: '',
                body: content,
                json_metadata: JSON.stringify({ app: 'wp-dapp/0.7.4', format: 'markdown' })
            }]
        ];

        $(this).text(wpdapp_frontend.i18n ? wpdapp_frontend.i18n.posting : 'Posting...').prop('disabled', true).addClass('loading');

        hive_keychain.requestBroadcast(usedUsername, operations, 'posting', function(response) {
            $(this).text(wpdapp_frontend.i18n ? wpdapp_frontend.i18n.submit : 'Submit').prop('disabled', false).removeClass('loading');
            if (response.success) {
                // Persist username after first success
                hiveUsername = usedUsername;
                try { localStorage.setItem('wpdapp_hive_username', hiveUsername); } catch(e) {}
                sessionStorage.setItem('wpdapp_hive_username', hiveUsername);
                // Update status chip/UI
                $form.find('.wpdapp-username, .wpdapp-username-label').remove();
                const $chip = $form.find('.wpdapp-status-chip');
                $chip.removeClass('not-connected').addClass('connected').text(wpdapp_frontend.i18n ? wpdapp_frontend.i18n.statusConnected : 'Connected');
                if ($form.find('.wpdapp-connected-as').length === 0) {
                    $chip.after('<p class="wpdapp-connected-as">' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.connectedAs : 'Connected as:') + ' ' + hiveUsername + '</p>');
                } else {
                    $form.find('.wpdapp-connected-as').text((wpdapp_frontend.i18n ? wpdapp_frontend.i18n.connectedAs : 'Connected as:') + ' ' + hiveUsername);
                }

                $form.append('<div class="wpdapp-form-success" role="status">' + (wpdapp_frontend.i18n ? wpdapp_frontend.i18n.postedSyncing : 'Reply posted successfully! Syncing...') + '</div>');
                if (isSyncInFlight) return;
                isSyncInFlight = true;
                $.ajax({
                    url: wpdapp_frontend.ajax_url,
                    type: 'POST',
                    data: { action: 'wpdapp_sync_comments', nonce: wpdapp_frontend.nonce, post_id: wpdapp_frontend.post_id },
                    success: function(syncResponse) {
                        if (syncResponse.success) {
                            $.ajax({
                                url: wpdapp_frontend.ajax_url,
                                type: 'POST',
                                data: { action: 'wpdapp_render_hive_comments', nonce: wpdapp_frontend.nonce, post_id: wpdapp_frontend.post_id },
                                success: function(renderResponse) {
                                    if (renderResponse.success && renderResponse.data && renderResponse.data.html) {
                                        var $footer = $('.wpdapp-hive-comments-footer').first();
                                        if ($footer.length) { $footer.remove(); }
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
                    complete: function() {
                        isSyncInFlight = false;
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

    // Handle sync button
    $(document).on('click', '.wpdapp-sync-button', function(e) {
        e.preventDefault();
        const $button = $(this);
        if (isSyncInFlight) return;
        isSyncInFlight = true;
        $button.text(wpdapp_frontend.i18n ? wpdapp_frontend.i18n.syncing : 'Syncing...').prop('disabled', true).addClass('loading');
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
                                var $footer = $('.wpdapp-hive-comments-footer').first();
                                if ($footer.length) {
                                    $footer.remove();
                                }
                                $('.wpdapp-hive-comments').first().replaceWith(renderResponse.data.html);
                            }
                        }
                    });
                }
            },
            complete: function() {
                isSyncInFlight = false;
                $button.text(wpdapp_frontend.i18n ? wpdapp_frontend.i18n.syncHiveComments : 'Sync Hive Comments').prop('disabled', false).removeClass('loading');
            }
        });
    });
});
