<?php

/*
 * Plugin Name:       wp-dapp
 * Plugin URI:        https://diggndeeper/wp-dapp/
 * Description:       Publish posts and pages to the Hive blockchain using Hive Keychain.
 * Version:           0.0.1
 * Requires at least: 5.6
 * Requires PHP:      7.0
 * Author:            DiggnDeeper
 * Author URI:        https://diggndeeper.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

// Enqueue the JavaScript file for the metabox
function wpdapp_enqueue_scripts() {
    wp_enqueue_script('wpdapp-metabox', plugin_dir_url(__FILE__) . 'wpdapp-metabox.js', array('jquery'), '1.0', true);
    wp_localize_script('wpdapp-metabox', 'wpdapp_metabox_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wpdapp_push_to_hive_nonce'),
    ));
}

add_action('admin_enqueue_scripts', 'wpdapp_enqueue_scripts');

// Add the metabox to the post edit page
function wpdapp_add_meta_box() {
    add_meta_box('wpdapp_meta_box', __('Hive Settings', 'wp-dapp'), 'wpdapp_meta_box_callback', 'post', 'side');
}
add_action('add_meta_boxes', 'wpdapp_add_meta_box');

// Callback function for the metabox
function wpdapp_meta_box_callback($post) {
    // Add a nonce field to the form
    wp_nonce_field('wpdapp_push_to_hive_nonce', '_wpnonce');

    // Get the current Hive username and option values
    $hive_username = get_post_meta($post->ID, 'wpdapp_hive_username', true);
    $hive_option = get_post_meta($post->ID, 'wpdapp_hive_option', true);

    // Output the metabox form
    ?>
    <label for="wpdapp_hive_username"><?php _e('Hive Username', 'wp-dapp'); ?></label>
    <input type="text" id="wpdapp_hive_username" name="wpdapp_hive_username" value="<?php echo esc_attr($hive_username); ?>">

    <label for="wpdapp_hive_option"><?php _e('Hive Option', 'wp-dapp'); ?></label>
    <select id="wpdapp_hive_option" name="wpdapp_hive_option">
        <option value="publish" <?php selected($hive_option, 'publish'); ?>><?php _e('Publish to Hive', 'wp-dapp'); ?></option>
        <option value="update" <?php selected($hive_option, 'update'); ?>><?php _e('Update on Hive', 'wp-dapp'); ?></option>
    </select>

    <button type="button" id="wpdapp-publish-to-hive-button"><?php _e('Publish to Hive', 'wp-dapp'); ?></button>
    <?php
}

// AJAX handler for pushing post to Hive
function wpdapp_handle_ajax_push_to_hive() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('error' => 'User not logged in.'));
    }

    // Verify the nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'wpdapp
_push_to_hive_nonce')) {
        wp_send_json_error(array('error' => 'Nonce verification failed.'));
    }

    // Get the post data from the AJAX request
    $post_id = sanitize_text_field($_POST['post_id']);
    $title = sanitize_text_field($_POST['title']);
    $content = wp_kses_post($_POST['content']);
    $tags = sanitize_text_field($_POST['tags']);
    $hive_username = sanitize_text_field($_POST['hive_username']);
    $hive_option = sanitize_text_field($_POST['hive_option']);

    // Publish the post to WordPress and Hive
    $post_data = array(
        'title' => $title,
        'body' => $content,
        'tags' => $tags,
    );
    wpdapp_publish_post($post_id, $hive_username, $hive_option, $post_data);

    // Send a success message
    wp_send_json_success(array('message' => 'Post published to Hive!'));
}
add_action('wp_ajax_wpdapp_push_to_hive', 'wpdapp_handle_ajax_push_to_hive');
add_action('wp_ajax_nopriv_wpdapp_push_to_hive', 'wpdapp_handle_ajax_push_to_hive');

// Function for publishing post to WordPress and Hive
function wpdapp_publish_post($post_id, $hive_username, $hive_option, $post_data) {
    // Check if the post has already been published to Hive
    $hive_post_id = get_post_meta($post_id, 'wpdapp_hive_post_id', true);
    if ($hive_post_id && $hive_option === 'publish') {
        // Post has already been published to Hive and user selected "Publish to Hive"
        return;
    } elseif (!$hive_post_id && $hive_option === 'update') {
        // Post has not been published to Hive and user selected "Update on Hive"
        return;
    }

    // Get the user's Hive private key
    $hive_key = get_user_meta(get_current_user_id(), 'wpdapp_hive_key', true);

    // Publish the post to Hive
    require_once('hive-api.php');
    $hive = new Hive();
    $hive->setKey($hive_key);
    try {
        $hive_post_id = $hive->publishPost($post_data['title'], $post_data['body'], $hive_username, $post_data['tags']);
    } catch (Exception $e) {
        wp_send_json_error(array('error' => 'Error publishing to Hive: ' . $e->getMessage()));
        return;
    }

    // Save the Hive post ID as post meta
    update_post_meta($post_id, 'wpdapp_hive_post_id', $hive_post_id);
}

// Save the Hive username and option values when the post is saved
function wpdapp_save_post($post_id) {
    if (isset($_POST['wpdapp_hive_username'])) {
        update_post_meta($post_id, 'wpdapp_hive_username', sanitize_text_field($_POST['wpdapp_hive_username']));
    }

    if (isset($_POST['wpdapp_hive_option'])) {
        update_post_meta($post_id, 'wpdapp_hive_option', sanitize_text_field($_POST['wpdapp_hive_option']));
    }
}
add_action('save_post', 'wpdapp_save_post');
