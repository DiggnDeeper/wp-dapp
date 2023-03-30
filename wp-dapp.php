 <?php

/*
 * Plugin Name:       Hive interface.
 * Plugin URI:        https://diggndeeper.com/wp-dapp/
 * Description:       Update, publish and more.
 * Version:           0.0.1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            DiggnDeeper
 * Author URI:        https://diggndeeper.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

// Add the Hive Keychain API script to the header
function add_hive_keychain_api_script() {
    wp_enqueue_script( 'hive-keychain-api', 'https://cdn.jsdelivr.net/npm/hive-keychain-api@1.2.0/index.js', array(), null, true );
}
add_action( 'wp_enqueue_scripts', 'add_hive_keychain_api_script' );

// Add a shortcode that displays a form to post a message to Hive
function hive_post_form_shortcode() {
    ob_start();
    ?>
    <form id="hive-post-form">
        <input type="text" name="message" placeholder="Enter your message">
        <button type="submit">Post to Hive</button>
    </form>
    <div id="hive-post-result"></div>
    <button onclick="wp_dapp_post_message()">Post Message</button>
    <?php
    return ob_get_clean();
}
add_shortcode( 'hive_post_form', 'hive_post_form_shortcode' );

// Define a function to post a custom JSON message to the blockchain.
function wp_dapp_post_message() {
    // Define the JSON message.
    $message = array(
        'app' => 'wp-dapp',
        'action' => 'post_message',
        'data' => array(
            'message' => 'Hello, Hive Keychain!',
        ),
    );

    // Use the hive_keychain_custom_json function to post the message.
    hive_keychain_custom_json( 'your-username', 'custom', 'wp-dapp', json_encode( $message ), '', function( $response ) {
        if ( $response ) {
            echo '<p>Your message was posted to the blockchain!</p>';
        } else {
            echo '<p>There was an error posting your message to the blockchain.</p>';
        }
    });
}

// Load the Hive Keychain library and define a function to check if it's installed
function check_hive_keychain() {
    ?>
    <script>
        function checkHiveKeychain() {
            if (window.hive_keychain) {
                console.log('Hive Keychain is installed.');
            } else {
                console.log('Hive Keychain is not installed.');
            }
        }
        checkHiveKeychain();
    </script>
    <?php
}
add_action( 'wp_head', 'check_hive_keychain' );
