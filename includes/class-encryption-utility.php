<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class WP_Dapp_Encryption_Utility
 * 
 * Handles secure storage and encryption of sensitive data like Hive private keys.
 */
class WP_Dapp_Encryption_Utility {

    /**
     * The encryption key used for encryption/decryption.
     *
     * @var string
     */
    private $encryption_key;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->encryption_key = $this->get_encryption_key();
    }

    /**
     * Get or generate a site-specific encryption key.
     * 
     * @return string The encryption key.
     */
    private function get_encryption_key() {
        // Try to get existing key
        $key = get_option('wpdapp_encryption_key');
        
        // If no key exists, generate one and store it
        if (empty($key)) {
            // Generate a secure random key
            if (function_exists('random_bytes')) {
                $key = bin2hex(random_bytes(32)); // 64 character hex string
            } elseif (function_exists('openssl_random_pseudo_bytes')) {
                $key = bin2hex(openssl_random_pseudo_bytes(32));
            } else {
                // Fallback to less secure but still usable method
                $key = md5(uniqid(mt_rand(), true) . AUTH_KEY . time());
            }
            
            // Store the key in the database
            update_option('wpdapp_encryption_key', $key, false);
        }
        
        // If AUTH_SALT is defined (it should be in wp-config.php), include it for additional security
        if (defined('AUTH_SALT')) {
            $key .= AUTH_SALT;
        }
        
        return $key;
    }

    /**
     * Encrypt sensitive data.
     * 
     * @param string $data The data to encrypt.
     * @return string|false The encrypted data or false on failure.
     */
    public function encrypt($data) {
        if (empty($data)) {
            return false;
        }
        
        // Use OpenSSL for encryption if available (preferred method)
        if (function_exists('openssl_encrypt')) {
            $iv_size = openssl_cipher_iv_length('aes-256-cbc');
            $iv = openssl_random_pseudo_bytes($iv_size);
            
            $encrypted = openssl_encrypt(
                $data,
                'aes-256-cbc',
                $this->encryption_key,
                0,
                $iv
            );
            
            if ($encrypted === false) {
                return false;
            }
            
            // Combine the IV and encrypted data for storage
            return base64_encode($iv . $encrypted);
        }
        
        // Fallback to a basic encryption method if OpenSSL is not available
        // Note: This is less secure, but better than plaintext
        $key = substr(md5($this->encryption_key), 0, 24);
        $result = '';
        
        for ($i = 0; $i < strlen($data); $i++) {
            $char = substr($data, $i, 1);
            $keychar = substr($key, ($i % strlen($key)) - 1, 1);
            $char = chr(ord($char) + ord($keychar));
            $result .= $char;
        }
        
        return base64_encode($result);
    }

    /**
     * Decrypt sensitive data.
     * 
     * @param string $encrypted_data The encrypted data to decrypt.
     * @return string|false The decrypted data or false on failure.
     */
    public function decrypt($encrypted_data) {
        if (empty($encrypted_data)) {
            return false;
        }
        
        // Decode the base64 encoded string
        $decoded = base64_decode($encrypted_data);
        if ($decoded === false) {
            return false;
        }
        
        // Use OpenSSL for decryption if available (preferred method)
        if (function_exists('openssl_decrypt')) {
            $iv_size = openssl_cipher_iv_length('aes-256-cbc');
            
            // Extract the IV and encrypted data
            $iv = substr($decoded, 0, $iv_size);
            $encrypted_data = substr($decoded, $iv_size);
            
            return openssl_decrypt(
                $encrypted_data,
                'aes-256-cbc',
                $this->encryption_key,
                0,
                $iv
            );
        }
        
        // Fallback to basic decryption method
        $key = substr(md5($this->encryption_key), 0, 24);
        $result = '';
        
        for ($i = 0; $i < strlen($decoded); $i++) {
            $char = substr($decoded, $i, 1);
            $keychar = substr($key, ($i % strlen($key)) - 1, 1);
            $char = chr(ord($char) - ord($keychar));
            $result .= $char;
        }
        
        return $result;
    }

    /**
     * Securely store sensitive data using encryption.
     * 
     * @param string $option_name The option name for storage.
     * @param string $value The sensitive value to store.
     * @return bool Whether the operation was successful.
     */
    public function store_secure_option($option_name, $value) {
        if (empty($option_name)) {
            return false;
        }
        
        if (empty($value)) {
            delete_option($option_name);
            return true;
        }
        
        $encrypted = $this->encrypt($value);
        if ($encrypted === false) {
            return false;
        }
        
        return update_option($option_name, $encrypted, false);
    }

    /**
     * Retrieve securely stored sensitive data.
     * 
     * @param string $option_name The option name to retrieve.
     * @return string|false The decrypted value or false on failure.
     */
    public function get_secure_option($option_name) {
        if (empty($option_name)) {
            return false;
        }
        
        $encrypted = get_option($option_name);
        if (empty($encrypted)) {
            return false;
        }
        
        return $this->decrypt($encrypted);
    }

    /**
     * Test if the credentials provided can be verified with the Hive API.
     * 
     * @param string $account Hive account name.
     * @param string $private_key Hive private posting key.
     * @return bool|WP_Error True if valid or WP_Error on failure.
     */
    public function test_hive_credentials($account, $private_key) {
        // Basic validation
        if (empty($account) || empty($private_key)) {
            return new WP_Error('invalid_credentials', 'Account name and private key are required');
        }
        
        // TODO: Implement actual verification with Hive API
        // For now, just return true if basic validation passes
        return true;
    }
} 