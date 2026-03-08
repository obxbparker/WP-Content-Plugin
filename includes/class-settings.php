<?php
/**
 * Plugin settings management.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ContentHub_Settings {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the Claude API key (decrypted).
     */
    public function get_api_key(): string {
        $encrypted = get_option( 'contenthub_api_key', '' );
        if ( empty( $encrypted ) ) {
            return '';
        }
        return $this->decrypt( $encrypted );
    }

    /**
     * Save the Claude API key (encrypted).
     */
    public function set_api_key( string $key ): bool {
        if ( empty( $key ) ) {
            return delete_option( 'contenthub_api_key' );
        }
        return update_option( 'contenthub_api_key', $this->encrypt( $key ) );
    }

    /**
     * Get general settings.
     */
    public function get_settings(): array {
        $raw = get_option( 'contenthub_settings', '{}' );
        $settings = json_decode( $raw, true );
        return is_array( $settings ) ? $settings : [];
    }

    /**
     * Save general settings.
     */
    public function save_settings( array $settings ): bool {
        return update_option( 'contenthub_settings', wp_json_encode( $settings ) );
    }

    /**
     * Check if the API key is configured.
     */
    public function has_api_key(): bool {
        return ! empty( $this->get_api_key() );
    }

    /**
     * Encrypt a value using WordPress salts.
     */
    private function encrypt( string $value ): string {
        if ( function_exists( 'sodium_crypto_secretbox' ) ) {
            $key = sodium_crypto_generichash( wp_salt( 'auth' ), '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES );
            $nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
            $cipher = sodium_crypto_secretbox( $value, $nonce, $key );
            return base64_encode( $nonce . $cipher );
        }
        // Fallback: simple obfuscation with auth salt (not truly secure, but better than plaintext).
        return base64_encode( openssl_encrypt( $value, 'AES-256-CBC', wp_salt( 'auth' ), 0, substr( md5( wp_salt( 'secure_auth' ) ), 0, 16 ) ) );
    }

    /**
     * Decrypt a value using WordPress salts.
     */
    private function decrypt( string $value ): string {
        $decoded = base64_decode( $value );
        if ( false === $decoded ) {
            return '';
        }

        if ( function_exists( 'sodium_crypto_secretbox_open' ) ) {
            $key = sodium_crypto_generichash( wp_salt( 'auth' ), '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES );
            $nonce = substr( $decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
            $cipher = substr( $decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
            $plain = sodium_crypto_secretbox_open( $cipher, $nonce, $key );
            return false === $plain ? '' : $plain;
        }

        // Fallback.
        $decrypted = openssl_decrypt( base64_decode( $decoded ), 'AES-256-CBC', wp_salt( 'auth' ), 0, substr( md5( wp_salt( 'secure_auth' ) ), 0, 16 ) );
        return false === $decrypted ? '' : $decrypted;
    }
}
