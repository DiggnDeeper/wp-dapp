console.log("wpdapp-metabox.js loaded");

jQuery(document).ready(function() {
    jQuery(document).on('click', '#wpdapp-publish-to-hive-button', function() {
        console.log("Publish to Hive button clicked"); // Added console.log
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

        console.log('Sending AJAX request:', data);

        jQuery.post(wpdapp_metabox_vars.ajax_url, data, function(response) {
            console.log('Received response:', response);

			console.log('Error:', response.data.error); // Added console.log

            if (response.success) {
                alert(response.data.message);
            } else {
                alert(response.data.error);
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.log('AJAX request failed:', textStatus, errorThrown); // Added console.log
            alert('An error occurred while trying to publish to Hive.');
        });
    });
});
