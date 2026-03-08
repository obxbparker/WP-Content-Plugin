<?php
/**
 * Registers the WP admin menu page and enqueues the React app.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ContentHub_Admin_Page {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    /**
     * Register the admin menu page.
     */
    public function register_menu(): void {
        add_menu_page(
            'OBX ContentHub',
            'OBX ContentHub',
            'manage_options',
            'contenthub-wp',
            [ $this, 'render_page' ],
            'dashicons-layout',
            30
        );
    }

    /**
     * Render the admin page (just a mount point for React).
     */
    public function render_page(): void {
        include CONTENTHUB_WP_PATH . 'admin/views/admin-page.php';
    }

    /**
     * Enqueue scripts and styles only on our admin page.
     */
    public function enqueue_scripts( string $hook ): void {
        if ( 'toplevel_page_contenthub-wp' !== $hook ) {
            return;
        }

        $asset_path = CONTENTHUB_WP_PATH . 'admin-ui/build/index.asset.php';

        if ( file_exists( $asset_path ) ) {
            $asset = require $asset_path;
        } else {
            $asset = [
                'dependencies' => [ 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n' ],
                'version'      => CONTENTHUB_WP_VERSION,
            ];
        }

        wp_enqueue_script(
            'contenthub-wp-admin',
            CONTENTHUB_WP_URL . 'admin-ui/build/index.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );

        wp_enqueue_style(
            'contenthub-wp-admin',
            CONTENTHUB_WP_URL . 'assets/css/admin.css',
            [ 'wp-components' ],
            CONTENTHUB_WP_VERSION
        );

        // Pass data to the React app.
        wp_localize_script( 'contenthub-wp-admin', 'contentHubWP', [
            'restUrl'  => rest_url( 'contenthub-wp/v1/' ),
            'nonce'    => wp_create_nonce( 'wp_rest' ),
            'siteUrl'  => get_site_url(),
            'siteName' => get_bloginfo( 'name' ),
        ] );
    }
}
