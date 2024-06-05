<?php
/*
Plugin Name: WP DAPP
Description: A WordPress plugin for publishing and displaying data from the Hive blockchain.
Version: 1.0
Author: Your Name
*/

function wp_dapp_enqueue_scripts() {
    wp_enqueue_script('hive-js', 'https://cdn.jsdelivr.net/npm/@hiveio/hive-js@latest/dist/hive.min.js', [], null, true);
    wp_enqueue_script('hive-keychain', 'https://cdn.jsdelivr.net/npm/hive-keychain@latest/dist/hive-keychain.min.js', [], null, true);
    wp_enqueue_script('wp-dapp-js', plugin_dir_url(__FILE__) . 'wp-dapp.js', [], null, true);
    wp_enqueue_style('wp-dapp-css', plugin_dir_url(__FILE__) . 'wp-dapp.css');
}
add_action('wp_enqueue_scripts', 'wp_dapp_enqueue_scripts');

function display_hive_data($atts) {
    $username = $atts['username'];
    return "<div id='content'></div><script>fetchHiveData('$username');</script>";
}
add_shortcode('hive_data', 'display_hive_data');
?>
