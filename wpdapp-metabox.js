 function wpdapp_push_to_hive() {
    const postId = document.getElementById('post_ID').value;
    const title = document.getElementById('title').value;
    const content = document.getElementById('content').value;
    const tags = ''; // Get the post tags, you may need a custom implementation depending on your setup
    const hiveUsername = document.getElementById('wpdapp_hive_username').value;
    const hiveOption = document.getElementById('wpdapp_hive_option').value;

    // Create a FormData object to send the data to the server
    const formData = new FormData();
    formData.append('action', 'wpdapp_push_to_hive');
    formData.append('_wpnonce', document.getElementById('_wpnonce').value);
    formData.append('post_id', postId);
    formData.append('title', title);
    formData.append('content', content);
    formData.append('tags', tags);
    formData.append('hive_username', hiveUsername);
    formData.append('hive_option', hiveOption);

    // Send the AJAX request
    fetch(ajaxurl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then((data) => {
            if (data.success) {
                alert(data.data.message);
            } else {
                alert(data.data.error);
            }
        })
        .catch((error) => {
            console.error('There was a problem with the fetch operation:', error);
        });
}
