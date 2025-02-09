<?php
/**
 * Plugin Name: wp-dapp
 * Description: Hive Keychain API
 * Version: .0.0
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
    <script>
        // Handle form submission
        document.getElementById( 'hive-post-form' ).addEventListener( 'submit', function( event ) {
            event.preventDefault();
            // Get the user's Hive account name and permission from Hive Keychain
            hive_keychain.requestAccount( function( account ) {
                if ( ! account ) {
                    alert( 'Please install and authorize Hive Keychain to use this feature.' );
                    return;
                }
                // Get the message from the form input
                var message = document.querySelector( 'input[name="message"]' ).value;
                // Construct the Hive transaction object
                var tx = {
                    'operations': [[
                        'comment',
                        {
                            'parent_author': '',
                            'parent_permlink': 'test',
                            'author': account.name,
                            'permlink': 'my-post',
                            'title': '',
                            'body': message,
                            'json_metadata': '{}'
                        }
                    ]]
                };
                // Broadcast the transaction to Hive
                hive_keychain.broadcast( account.name, 'posting', tx, function( response ) {
                    if ( response.success ) {
                        document.getElementById( 'hive-post-result' ).innerHTML = 'Your message was posted to Hive!';
                    } else {
                        document.getElementById( 'hive-post-result' ).innerHTML = 'There was an error posting your message to Hive.';
                    }
                });
            });
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode( 'hive_post_form', 'hive_post_form_shortcode' );
