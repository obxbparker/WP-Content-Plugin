<?php
/**
 * Core plugin orchestrator.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ContentHub_WP {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_includes();
        $this->init_hooks();
    }

    private function load_includes() {
        $includes = [
            'class-settings.php',
            'class-template-registry.php',
            'class-page-discovery.php',
            'class-elementor-parser.php',
            'class-template-mapper.php',
            'class-content-scraper.php',
            'class-content-generator.php',
            'class-content-deployer.php',
            'class-business-profile.php',
            'class-share-token.php',
            'class-rest-api.php',
        ];

        foreach ( $includes as $file ) {
            require_once CONTENTHUB_WP_PATH . 'includes/' . $file;
        }

        require_once CONTENTHUB_WP_PATH . 'admin/class-admin-page.php';
    }

    private function init_hooks() {
        // Initialize REST API.
        add_action( 'rest_api_init', [ ContentHub_REST_API::instance(), 'register_routes' ] );

        // Public share form.
        add_action( 'template_redirect', [ $this, 'maybe_render_share_form' ] );

        // Live preview renderer (admin).
        add_action( 'template_redirect', [ $this, 'maybe_render_preview' ] );

        // Portal preview renderer (public, token-based).
        add_action( 'template_redirect', [ $this, 'maybe_render_portal_preview' ] );

        // Initialize admin page.
        if ( is_admin() ) {
            ContentHub_Admin_Page::instance();
        }
    }

    /**
     * Intercept requests with ?contenthub_share=TOKEN and render the client portal.
     */
    public function maybe_render_share_form(): void {
        $token = isset( $_GET['contenthub_share'] ) ? sanitize_text_field( wp_unslash( $_GET['contenthub_share'] ) ) : '';
        if ( empty( $token ) ) {
            return;
        }

        if ( ! ContentHub_Share_Token::instance()->validate( $token ) ) {
            wp_die( 'This link is no longer valid.', 'Link Expired', [ 'response' => 403 ] );
        }

        include CONTENTHUB_WP_PATH . 'public/portal.php';
        exit;
    }

    /**
     * Intercept requests with ?contenthub_preview=PAGE_ID and render a live preview.
     */
    public function maybe_render_preview(): void {
        if ( ! isset( $_GET['contenthub_preview'] ) ) {
            return;
        }

        $page_id = absint( $_GET['contenthub_preview'] );
        $nonce   = isset( $_GET['_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_nonce'] ) ) : '';

        // Verify user is logged in and has permission.
        if ( ! is_user_logged_in() || ! current_user_can( 'edit_posts' ) ) {
            wp_die( 'Unauthorized.', 'Preview Error', [ 'response' => 403 ] );
        }

        // Verify nonce.
        if ( ! wp_verify_nonce( $nonce, "contenthub_preview_{$page_id}" ) ) {
            wp_die( 'Invalid or expired preview link.', 'Preview Error', [ 'response' => 403 ] );
        }

        // Load content from transient.
        $user_id       = get_current_user_id();
        $transient_key = "contenthub_preview_{$page_id}_{$user_id}";
        $raw_content   = get_transient( $transient_key );

        if ( false === $raw_content ) {
            wp_die( 'Preview data has expired. Please try again.', 'Preview Error', [ 'response' => 410 ] );
        }

        $content_data = json_decode( $raw_content, true );
        if ( ! is_array( $content_data ) ) {
            wp_die( 'Invalid preview data.', 'Preview Error', [ 'response' => 400 ] );
        }

        // Get the page's template type and mapping.
        $template_slug = ContentHub_Page_Discovery::instance()->get_template_type( $page_id );
        if ( empty( $template_slug ) ) {
            wp_die( 'Page has no template type.', 'Preview Error', [ 'response' => 400 ] );
        }

        $registry = ContentHub_Template_Registry::instance();
        $template = $registry->get( $template_slug );
        $template_id = (int) ( $template['elementor_template_id'] ?? 0 );

        if ( ! $template_id ) {
            wp_die( 'No Elementor template assigned for this template type.', 'Preview Error', [ 'response' => 400 ] );
        }

        // Get the mapping and Elementor template data.
        $mapper  = ContentHub_Template_Mapper::instance();
        $mapping = $mapper->get_mapping( $template_slug );
        $parser  = ContentHub_Elementor_Parser::instance();
        $elementor_data = $parser->get_elementor_data( $template_id );

        if ( null === $elementor_data || empty( $mapping ) ) {
            wp_die( 'Could not load template data.', 'Preview Error', [ 'response' => 500 ] );
        }

        // Apply content to the Elementor data in memory.
        $preview_data = $mapper->apply_content( $elementor_data, $mapping, $content_data );
        $preview_json = wp_json_encode( $preview_data );

        // Override Elementor data for this page via metadata filter.
        $meta_filter = function ( $value, $object_id, $meta_key ) use ( $page_id, $preview_json ) {
            if ( (int) $object_id === $page_id && '_elementor_data' === $meta_key ) {
                return [ $preview_json ];
            }
            return $value;
        };
        add_filter( 'get_post_metadata', $meta_filter, 10, 3 );

        // Render the page using Elementor.
        if ( ! class_exists( '\Elementor\Plugin' ) ) {
            wp_die( 'Elementor is not active.', 'Preview Error', [ 'response' => 500 ] );
        }

        // Use the target page for rendering (it has the Canvas template set).
        $post = get_post( $page_id );
        if ( ! $post ) {
            wp_die( 'Page not found.', 'Preview Error', [ 'response' => 404 ] );
        }

        // Disable admin bar and Elementor editor UI in preview.
        show_admin_bar( false );
        add_filter( 'elementor/frontend/admin_bar/settings', '__return_empty_array' );

        // Set up global post data.
        global $wp_query;
        $wp_query->is_singular = true;
        $wp_query->is_page     = true;
        $GLOBALS['post']       = $post;
        setup_postdata( $post );

        // Render using Elementor and output clean HTML.
        $builder_content = \Elementor\Plugin::$instance->frontend->get_builder_content( $page_id, true );

        // Get Elementor's enqueued styles.
        ob_start();
        wp_head();
        $head = ob_get_clean();

        ob_start();
        wp_footer();
        $footer = ob_get_clean();

        echo '<!DOCTYPE html><html ' . get_language_attributes() . '>';
        echo '<head><meta charset="' . get_bloginfo( 'charset' ) . '">';
        echo '<meta name="viewport" content="width=1280">';
        echo '<style>html,body{margin:0!important;padding:0!important;}#wpadminbar{display:none!important;}</style>';
        echo $head;
        echo '</head><body class="elementor-page elementor-default">';
        echo $builder_content;
        echo $footer;
        echo '</body></html>';

        remove_filter( 'get_post_metadata', $meta_filter );
        exit;
    }

    /**
     * Intercept portal preview requests (public, nonce-verified).
     */
    public function maybe_render_portal_preview(): void {
        if ( ! isset( $_GET['contenthub_portal_preview'] ) ) {
            return;
        }

        $page_id    = absint( $_GET['contenthub_portal_preview'] );
        $sig        = isset( $_GET['_sig'] ) ? sanitize_text_field( wp_unslash( $_GET['_sig'] ) ) : '';
        $token_hash = isset( $_GET['_th'] ) ? sanitize_text_field( wp_unslash( $_GET['_th'] ) ) : '';

        // Verify HMAC signature (nonces don't work for anonymous portal users).
        $expected = hash_hmac( 'sha256', "{$page_id}:{$token_hash}", wp_salt( 'nonce' ) );
        if ( ! hash_equals( $expected, $sig ) ) {
            wp_die( 'Invalid or expired preview link.', 'Preview Error', [ 'response' => 403 ] );
        }

        // Load content from transient.
        $transient_key = "contenthub_preview_{$page_id}_portal_{$token_hash}";
        $raw_content   = get_transient( $transient_key );

        if ( false === $raw_content ) {
            wp_die( 'Preview data has expired. Please try again.', 'Preview Error', [ 'response' => 410 ] );
        }

        $content_data = json_decode( $raw_content, true );
        if ( ! is_array( $content_data ) ) {
            wp_die( 'Invalid preview data.', 'Preview Error', [ 'response' => 400 ] );
        }

        // Get the page's template type and mapping.
        $template_slug = ContentHub_Page_Discovery::instance()->get_template_type( $page_id );
        if ( empty( $template_slug ) ) {
            wp_die( 'Page has no template type.', 'Preview Error', [ 'response' => 400 ] );
        }

        $registry    = ContentHub_Template_Registry::instance();
        $template    = $registry->get( $template_slug );
        $template_id = (int) ( $template['elementor_template_id'] ?? 0 );

        if ( ! $template_id ) {
            wp_die( 'No Elementor template assigned for this template type.', 'Preview Error', [ 'response' => 400 ] );
        }

        $mapper         = ContentHub_Template_Mapper::instance();
        $mapping        = $mapper->get_mapping( $template_slug );
        $parser         = ContentHub_Elementor_Parser::instance();
        $elementor_data = $parser->get_elementor_data( $template_id );

        if ( null === $elementor_data || empty( $mapping ) ) {
            wp_die( 'Could not load template data.', 'Preview Error', [ 'response' => 500 ] );
        }

        $preview_data = $mapper->apply_content( $elementor_data, $mapping, $content_data );
        $preview_json = wp_json_encode( $preview_data );

        $meta_filter = function ( $value, $object_id, $meta_key ) use ( $page_id, $preview_json ) {
            if ( (int) $object_id === $page_id && '_elementor_data' === $meta_key ) {
                return [ $preview_json ];
            }
            return $value;
        };
        add_filter( 'get_post_metadata', $meta_filter, 10, 3 );

        if ( ! class_exists( '\Elementor\Plugin' ) ) {
            wp_die( 'Elementor is not active.', 'Preview Error', [ 'response' => 500 ] );
        }

        $post = get_post( $page_id );
        if ( ! $post ) {
            wp_die( 'Page not found.', 'Preview Error', [ 'response' => 404 ] );
        }

        // Disable admin bar.
        show_admin_bar( false );
        add_filter( 'elementor/frontend/admin_bar/settings', '__return_empty_array' );

        global $wp_query;
        $wp_query->is_singular = true;
        $wp_query->is_page     = true;
        $GLOBALS['post']       = $post;
        setup_postdata( $post );

        // Render using Elementor and output clean HTML.
        $builder_content = \Elementor\Plugin::$instance->frontend->get_builder_content( $page_id, true );

        ob_start();
        wp_head();
        $head = ob_get_clean();

        ob_start();
        wp_footer();
        $footer = ob_get_clean();

        echo '<!DOCTYPE html><html ' . get_language_attributes() . '>';
        echo '<head><meta charset="' . get_bloginfo( 'charset' ) . '">';
        echo '<meta name="viewport" content="width=1280">';
        echo '<style>html,body{margin:0!important;padding:0!important;}#wpadminbar{display:none!important;}</style>';
        echo $head;
        echo '</head><body class="elementor-page elementor-default">';
        echo $builder_content;
        echo $footer;
        echo '</body></html>';

        remove_filter( 'get_post_metadata', $meta_filter );
        exit;
    }
}
