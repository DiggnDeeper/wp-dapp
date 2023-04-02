console.log("wpdapp-metabox.js loaded"); // Add this line

jQuery(document).ready(function() {
    jQuery(document).on('click', '#wpdapp-publish-to-hive-button', function() {
        const postId = jQuery('#post_ID').val();
        const title = jQuery('#title').val();
        const content = jQuery('#content').val();
        const tags = jQuery('#new-tag-post_tag').val();
        const hiveUsername = jQuery('#wpdapp_hive_username').val();
        const hiveOption = jQuery('#wpdapp_hive_option').val();

        const data = {
            action: 'wpdapp_push_to_hive',
            post_id: postId,
            title: title,
            content: content,
            tags: tags,
            hive_username: hiveUsername,
            hive_option: hiveOption,
            _wpnonce: wpdapp_metabox_vars.nonce,
        };

        console.log('Sending AJAX request:', data); // Added console.log

        jQuery.post(wpdapp_metabox_vars.ajax_url, data, function(response) {
            console.log('Received response:', response); // Added console.log

            if (response.success) {
                alert(response.data.message);
            } else {
                alert(response.data.error);
            }
        });
    });
});
