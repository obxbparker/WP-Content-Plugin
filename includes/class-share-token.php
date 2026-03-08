<?php
/**
 * Share token management for public business profile form.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ContentHub_Share_Token {

    private static $instance = null;
    private const OPTION_KEY = 'contenthub_share_token';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get current share token data, or null if none exists.
     */
    public function get(): ?array {
        $raw = get_option( self::OPTION_KEY, '' );
        if ( empty( $raw ) ) {
            return null;
        }

        $data = json_decode( $raw, true );
        if ( ! is_array( $data ) || empty( $data['token'] ) ) {
            return null;
        }

        return $data;
    }

    /**
     * Generate a new share token.
     */
    public function generate(): array {
        $data = [
            'token'      => wp_generate_password( 32, false ),
            'created_at' => current_time( 'mysql' ),
        ];

        update_option( self::OPTION_KEY, wp_json_encode( $data ) );

        return $data;
    }

    /**
     * Revoke the current share token.
     */
    public function revoke(): void {
        delete_option( self::OPTION_KEY );
    }

    /**
     * Validate a token string.
     */
    public function validate( string $token ): bool {
        $data = $this->get();
        if ( ! $data ) {
            return false;
        }

        return hash_equals( $data['token'], $token );
    }

    /**
     * Get the share URL.
     */
    public function get_share_url(): ?string {
        $data = $this->get();
        if ( ! $data ) {
            return null;
        }

        return add_query_arg( 'contenthub_share', $data['token'], home_url( '/' ) );
    }
}
