<?php
/**
 * REST API endpoints for ContentHub WP.
 *
 * All endpoints require 'manage_options' capability (admin only).
 * Namespace: contenthub-wp/v1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ContentHub_REST_API {

    private static $instance = null;
    private const NAMESPACE = 'contenthub-wp/v1';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register all REST routes.
     */
    public function register_routes(): void {
        // Pages.
        register_rest_route( self::NAMESPACE, '/pages', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_pages' ],
            'permission_callback' => [ $this, 'check_permissions' ],
        ] );

        register_rest_route( self::NAMESPACE, '/pages/(?P<id>\d+)/assign-template', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'assign_template' ],
            'permission_callback' => [ $this, 'check_permissions' ],
            'args'                => [
                'template_slug' => [ 'type' => 'string', 'required' => true ],
            ],
        ] );

        register_rest_route( self::NAMESPACE, '/pages/(?P<id>\d+)/content', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_page_content' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'save_page_content' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
        ] );

        register_rest_route( self::NAMESPACE, '/pages/(?P<id>\d+)/scrape', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'scrape_page' ],
            'permission_callback' => [ $this, 'check_permissions' ],
            'args'                => [
                'url' => [ 'type' => 'string', 'required' => true, 'format' => 'uri' ],
            ],
        ] );

        register_rest_route( self::NAMESPACE, '/pages/(?P<id>\d+)/generate', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'generate_page_content' ],
            'permission_callback' => [ $this, 'check_permissions' ],
        ] );

        register_rest_route( self::NAMESPACE, '/pages/(?P<id>\d+)/deploy', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'deploy_page' ],
            'permission_callback' => [ $this, 'check_permissions' ],
        ] );

        register_rest_route( self::NAMESPACE, '/pages/(?P<id>\d+)/rollback', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'rollback_page' ],
            'permission_callback' => [ $this, 'check_permissions' ],
        ] );

        register_rest_route( self::NAMESPACE, '/pages/(?P<id>\d+)/context', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_page_context' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'save_page_context' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
        ] );

        register_rest_route( self::NAMESPACE, '/pages/(?P<id>\d+)/preview', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'preview_page' ],
            'permission_callback' => [ $this, 'check_permissions' ],
        ] );

        // Template types.
        register_rest_route( self::NAMESPACE, '/template-types', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_template_types' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'create_template_type' ],
                'permission_callback' => [ $this, 'check_permissions' ],
                'args'                => [
                    'name' => [ 'type' => 'string', 'required' => true ],
                ],
            ],
        ] );

        register_rest_route( self::NAMESPACE, '/template-types/(?P<slug>[a-z0-9-]+)', [
            [
                'methods'             => 'PUT',
                'callback'            => [ $this, 'update_template_type' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ $this, 'delete_template_type' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
        ] );

        register_rest_route( self::NAMESPACE, '/template-types/(?P<slug>[a-z0-9-]+)/set-template', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'set_elementor_template' ],
            'permission_callback' => [ $this, 'check_permissions' ],
            'args'                => [
                'template_id' => [ 'type' => 'integer', 'required' => true ],
            ],
        ] );

        // List Elementor Library templates.
        register_rest_route( self::NAMESPACE, '/elementor-templates', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_elementor_templates' ],
            'permission_callback' => [ $this, 'check_permissions' ],
        ] );

        register_rest_route( self::NAMESPACE, '/template-types/(?P<slug>[a-z0-9-]+)/blueprint', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_blueprint' ],
            'permission_callback' => [ $this, 'check_permissions' ],
        ] );

        register_rest_route( self::NAMESPACE, '/template-types/(?P<slug>[a-z0-9-]+)/mapping', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_mapping' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'save_mapping' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
        ] );

        // Batch deploy.
        register_rest_route( self::NAMESPACE, '/deploy/batch/(?P<slug>[a-z0-9-]+)', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'deploy_batch' ],
            'permission_callback' => [ $this, 'check_permissions' ],
        ] );

        // Business profile.
        register_rest_route( self::NAMESPACE, '/business-profile', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_business_profile' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'save_business_profile' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
        ] );

        // Share token management (admin).
        register_rest_route( self::NAMESPACE, '/share-token', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_share_token' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'generate_share_token' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ $this, 'revoke_share_token' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
        ] );

        // Public business profile (token-authenticated).
        register_rest_route( self::NAMESPACE, '/public/business-profile', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'public_get_business_profile' ],
                'permission_callback' => [ $this, 'check_share_token' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'public_save_business_profile' ],
                'permission_callback' => [ $this, 'check_share_token' ],
            ],
        ] );

        // Settings.
        register_rest_route( self::NAMESPACE, '/settings', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_settings' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'save_settings' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
        ] );

        // Portal page visibility (admin).
        register_rest_route( self::NAMESPACE, '/portal-visibility', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_portal_visibility' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'save_portal_visibility' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ],
        ] );

        // Public portal endpoints (token-authenticated).
        register_rest_route( self::NAMESPACE, '/public/portal-config', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'public_get_portal_config' ],
            'permission_callback' => [ $this, 'check_share_token' ],
        ] );

        register_rest_route( self::NAMESPACE, '/public/pages', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'public_get_pages' ],
            'permission_callback' => [ $this, 'check_share_token' ],
        ] );

        register_rest_route( self::NAMESPACE, '/public/pages/(?P<id>\d+)/content', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'public_get_page_content' ],
                'permission_callback' => [ $this, 'check_share_token' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'public_save_page_content' ],
                'permission_callback' => [ $this, 'check_share_token' ],
            ],
        ] );

        register_rest_route( self::NAMESPACE, '/public/template-types/(?P<slug>[a-z0-9-]+)/mapping', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'public_get_mapping' ],
            'permission_callback' => [ $this, 'check_share_token' ],
        ] );

        register_rest_route( self::NAMESPACE, '/public/pages/(?P<id>\d+)/context', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'public_get_page_context' ],
                'permission_callback' => [ $this, 'check_share_token' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'public_save_page_context' ],
                'permission_callback' => [ $this, 'check_share_token' ],
            ],
        ] );

        register_rest_route( self::NAMESPACE, '/public/pages/(?P<id>\d+)/scrape', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'public_scrape_page' ],
            'permission_callback' => [ $this, 'check_share_token' ],
            'args'                => [
                'url' => [ 'type' => 'string', 'required' => true, 'format' => 'uri' ],
            ],
        ] );

        register_rest_route( self::NAMESPACE, '/public/pages/(?P<id>\d+)/generate', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'public_generate_page_content' ],
            'permission_callback' => [ $this, 'check_share_token' ],
        ] );

        register_rest_route( self::NAMESPACE, '/public/pages/(?P<id>\d+)/preview', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'public_preview_page' ],
            'permission_callback' => [ $this, 'check_share_token' ],
        ] );
    }

    // -------------------------------------------------------------------------
    // Permission check
    // -------------------------------------------------------------------------

    public function check_permissions(): bool {
        return current_user_can( 'manage_options' );
    }

    // -------------------------------------------------------------------------
    // Pages
    // -------------------------------------------------------------------------

    public function get_pages( WP_REST_Request $request ): WP_REST_Response {
        $pages = ContentHub_Page_Discovery::instance()->get_all_pages();
        return new WP_REST_Response( $pages, 200 );
    }

    public function assign_template( WP_REST_Request $request ): WP_REST_Response {
        $page_id = (int) $request['id'];
        $slug    = sanitize_key( $request->get_param( 'template_slug' ) );

        $discovery = ContentHub_Page_Discovery::instance();
        $discovery->assign_template( $page_id, $slug );

        return new WP_REST_Response( $discovery->get_page( $page_id ), 200 );
    }

    public function get_page_content( WP_REST_Request $request ): WP_REST_Response {
        $page_id = (int) $request['id'];
        $raw = get_post_meta( $page_id, '_contenthub_content_data', true );
        $data = ! empty( $raw ) ? json_decode( $raw, true ) : null;

        return new WP_REST_Response( [
            'page_id' => $page_id,
            'data'    => $data,
            'source'  => get_post_meta( $page_id, '_contenthub_content_source', true ),
            'status'  => get_post_meta( $page_id, '_contenthub_content_status', true ),
        ], 200 );
    }

    public function save_page_content( WP_REST_Request $request ): WP_REST_Response {
        $page_id = (int) $request['id'];
        $data    = $request->get_json_params();

        $content = $data['data'] ?? $data;
        $source  = sanitize_key( $data['source'] ?? 'manual' );

        update_post_meta( $page_id, '_contenthub_content_data', wp_json_encode( $content ) );
        update_post_meta( $page_id, '_contenthub_content_source', $source );
        update_post_meta( $page_id, '_contenthub_content_status', 'ready' );

        return new WP_REST_Response( [ 'success' => true ], 200 );
    }

    public function scrape_page( WP_REST_Request $request ): WP_REST_Response {
        $page_id = (int) $request['id'];
        $url     = esc_url_raw( $request->get_param( 'url' ) );

        // Get template info for this page.
        $template_slug = ContentHub_Page_Discovery::instance()->get_template_type( $page_id );
        if ( empty( $template_slug ) ) {
            return new WP_REST_Response( [ 'error' => 'Page has no template type assigned.' ], 400 );
        }

        $mapping = ContentHub_Template_Mapper::instance()->get_mapping( $template_slug );
        if ( empty( $mapping ) ) {
            return new WP_REST_Response( [ 'error' => 'No field mapping for this template type.' ], 400 );
        }

        $page = get_post( $page_id );
        $page_name = $page ? $page->post_title : 'Unknown';

        $scraper = ContentHub_Content_Scraper::instance();
        $result = $scraper->scrape_and_extract( $url, $page_name, $template_slug, $mapping );

        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( [ 'error' => $result->get_error_message() ], 500 );
        }

        // Save the extracted content.
        update_post_meta( $page_id, '_contenthub_content_data', wp_json_encode( $result ) );
        update_post_meta( $page_id, '_contenthub_content_source', 'scraped' );
        update_post_meta( $page_id, '_contenthub_content_status', 'ready' );

        return new WP_REST_Response( [
            'success' => true,
            'data'    => $result,
        ], 200 );
    }

    public function generate_page_content( WP_REST_Request $request ): WP_REST_Response {
        $page_id = (int) $request['id'];

        $template_slug = ContentHub_Page_Discovery::instance()->get_template_type( $page_id );
        if ( empty( $template_slug ) ) {
            return new WP_REST_Response( [ 'error' => 'Page has no template type assigned.' ], 400 );
        }

        $mapping = ContentHub_Template_Mapper::instance()->get_mapping( $template_slug );
        if ( empty( $mapping ) ) {
            return new WP_REST_Response( [ 'error' => 'No field mapping for this template type.' ], 400 );
        }

        $page = get_post( $page_id );
        $page_name = $page ? $page->post_title : 'Unknown';

        $profile = ContentHub_Business_Profile::instance();
        $business_profile = $profile->get();
        $company_name = $profile->get_company_name();

        // Load page context if available.
        $raw_context  = get_post_meta( $page_id, '_contenthub_page_context', true );
        $page_context = ! empty( $raw_context ) ? json_decode( $raw_context, true ) : [];

        // Merge in any context overrides from request body (from the modal).
        $req_data = $request->get_json_params();
        if ( ! empty( $req_data['page_context'] ) && is_array( $req_data['page_context'] ) ) {
            $page_context = array_merge( $page_context, $req_data['page_context'] );
            // Persist the updated context.
            update_post_meta( $page_id, '_contenthub_page_context', wp_json_encode( $page_context ) );
        }

        // Handle uploaded file content.
        $file_content = '';
        if ( ! empty( $page_context['uploaded_file_id'] ) ) {
            $file_path = get_attached_file( (int) $page_context['uploaded_file_id'] );
            if ( $file_path && file_exists( $file_path ) ) {
                $file_content = file_get_contents( $file_path );
                if ( mb_strlen( $file_content ) > 6000 ) {
                    $file_content = mb_substr( $file_content, 0, 6000 ) . "\n[File content truncated...]";
                }
            }
        }

        $generator = ContentHub_Content_Generator::instance();
        $result = $generator->generate( $page_name, $template_slug, $mapping, $company_name, $business_profile, $page_context, $file_content );

        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( [ 'error' => $result->get_error_message() ], 500 );
        }

        // Save the generated content.
        update_post_meta( $page_id, '_contenthub_content_data', wp_json_encode( $result ) );
        update_post_meta( $page_id, '_contenthub_content_source', 'ai_generated' );
        update_post_meta( $page_id, '_contenthub_content_status', 'ready' );

        return new WP_REST_Response( [
            'success' => true,
            'data'    => $result,
        ], 200 );
    }

    public function deploy_page( WP_REST_Request $request ): WP_REST_Response {
        $page_id = (int) $request['id'];

        $template_slug = ContentHub_Page_Discovery::instance()->get_template_type( $page_id );
        if ( empty( $template_slug ) ) {
            return new WP_REST_Response( [ 'error' => 'Page has no template type assigned.' ], 400 );
        }

        $raw_content = get_post_meta( $page_id, '_contenthub_content_data', true );
        if ( empty( $raw_content ) ) {
            return new WP_REST_Response( [ 'error' => 'No content data to deploy.' ], 400 );
        }

        $content = json_decode( $raw_content, true );
        if ( ! is_array( $content ) ) {
            return new WP_REST_Response( [ 'error' => 'Invalid content data.' ], 400 );
        }

        $deployer = ContentHub_Content_Deployer::instance();
        $result = $deployer->deploy( $page_id, $content, $template_slug );

        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( [ 'error' => $result->get_error_message() ], 500 );
        }

        return new WP_REST_Response( [ 'success' => true ], 200 );
    }

    public function rollback_page( WP_REST_Request $request ): WP_REST_Response {
        $page_id = (int) $request['id'];

        $deployer = ContentHub_Content_Deployer::instance();
        $result = $deployer->rollback( $page_id );

        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( [ 'error' => $result->get_error_message() ], 500 );
        }

        return new WP_REST_Response( [ 'success' => true ], 200 );
    }

    public function get_page_context( WP_REST_Request $request ): WP_REST_Response {
        $page_id = (int) $request['id'];
        $raw     = get_post_meta( $page_id, '_contenthub_page_context', true );
        $data    = ! empty( $raw ) ? json_decode( $raw, true ) : [];

        return new WP_REST_Response( [
            'page_id' => $page_id,
            'context' => $data,
        ], 200 );
    }

    public function save_page_context( WP_REST_Request $request ): WP_REST_Response {
        $page_id = (int) $request['id'];
        $data    = $request->get_json_params();
        $context = $data['context'] ?? $data;

        // Sanitize context fields.
        $sanitized = [
            'page_topic'       => sanitize_textarea_field( $context['page_topic'] ?? '' ),
            'target_keywords'  => sanitize_textarea_field( $context['target_keywords'] ?? '' ),
            'page_goal'        => sanitize_text_field( $context['page_goal'] ?? '' ),
            'target_audience'  => sanitize_textarea_field( $context['target_audience'] ?? '' ),
            'reference_url'    => esc_url_raw( $context['reference_url'] ?? '' ),
            'ai_notes'         => sanitize_textarea_field( $context['ai_notes'] ?? '' ),
            'uploaded_file_id' => absint( $context['uploaded_file_id'] ?? 0 ),
        ];

        update_post_meta( $page_id, '_contenthub_page_context', wp_json_encode( $sanitized ) );

        return new WP_REST_Response( [ 'success' => true, 'context' => $sanitized ], 200 );
    }

    public function preview_page( WP_REST_Request $request ): WP_REST_Response {
        $page_id = (int) $request['id'];
        $data    = $request->get_json_params();
        $content = $data['data'] ?? $data;
        $user_id = get_current_user_id();

        $transient_key = "contenthub_preview_{$page_id}_{$user_id}";
        set_transient( $transient_key, wp_json_encode( $content ), 300 );

        $nonce = wp_create_nonce( "contenthub_preview_{$page_id}" );
        $url   = add_query_arg( [
            'contenthub_preview' => $page_id,
            '_nonce'             => $nonce,
        ], home_url( '/' ) );

        return new WP_REST_Response( [ 'url' => $url ], 200 );
    }

    // -------------------------------------------------------------------------
    // Template Types
    // -------------------------------------------------------------------------

    public function get_template_types( WP_REST_Request $request ): WP_REST_Response {
        return new WP_REST_Response( ContentHub_Template_Registry::instance()->get_all(), 200 );
    }

    public function create_template_type( WP_REST_Request $request ): WP_REST_Response {
        $name = sanitize_text_field( $request->get_param( 'name' ) );
        if ( empty( $name ) ) {
            return new WP_REST_Response( [ 'error' => 'Template type name is required.' ], 400 );
        }

        $type = ContentHub_Template_Registry::instance()->create( $name );
        return new WP_REST_Response( $type, 201 );
    }

    public function update_template_type( WP_REST_Request $request ): WP_REST_Response {
        $slug = sanitize_key( $request['slug'] );
        $data = $request->get_json_params();

        $result = ContentHub_Template_Registry::instance()->update( $slug, $data );
        if ( null === $result ) {
            return new WP_REST_Response( [ 'error' => 'Template type not found.' ], 404 );
        }

        return new WP_REST_Response( $result, 200 );
    }

    public function delete_template_type( WP_REST_Request $request ): WP_REST_Response {
        $slug = sanitize_key( $request['slug'] );

        $deleted = ContentHub_Template_Registry::instance()->delete( $slug );
        if ( ! $deleted ) {
            return new WP_REST_Response( [ 'error' => 'Template type not found.' ], 404 );
        }

        return new WP_REST_Response( [ 'success' => true ], 200 );
    }

    public function set_elementor_template( WP_REST_Request $request ): WP_REST_Response {
        $slug        = sanitize_key( $request['slug'] );
        $template_id = (int) $request->get_param( 'template_id' );

        $result = ContentHub_Template_Registry::instance()->set_elementor_template( $slug, $template_id );
        if ( null === $result ) {
            return new WP_REST_Response( [ 'error' => 'Template type not found.' ], 404 );
        }

        // Auto-generate blueprint and mapping from the Elementor template.
        $parser = ContentHub_Elementor_Parser::instance();
        $blueprint = $parser->build_blueprint( $template_id );

        if ( $blueprint ) {
            $mapper = ContentHub_Template_Mapper::instance();
            $auto_mapping = $mapper->auto_map( $blueprint );
            $mapper->save_mapping( $slug, $auto_mapping );
        }

        return new WP_REST_Response( $result, 200 );
    }

    public function get_elementor_templates( WP_REST_Request $request ): WP_REST_Response {
        $templates = get_posts( [
            'post_type'      => 'elementor_library',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ] );

        $result = [];
        foreach ( $templates as $tpl ) {
            $type = get_post_meta( $tpl->ID, '_elementor_template_type', true );
            $result[] = [
                'id'    => $tpl->ID,
                'title' => $tpl->post_title,
                'type'  => $type ?: 'page',
            ];
        }

        return new WP_REST_Response( $result, 200 );
    }

    public function get_blueprint( WP_REST_Request $request ): WP_REST_Response {
        $slug = sanitize_key( $request['slug'] );
        $template = ContentHub_Template_Registry::instance()->get( $slug );

        if ( ! $template || empty( $template['elementor_template_id'] ) ) {
            return new WP_REST_Response( [ 'error' => 'No Elementor template assigned for this template type.' ], 404 );
        }

        $blueprint = ContentHub_Elementor_Parser::instance()->build_blueprint( $template['elementor_template_id'] );
        if ( null === $blueprint ) {
            return new WP_REST_Response( [ 'error' => 'Could not parse Elementor data from the assigned template.' ], 500 );
        }

        return new WP_REST_Response( $blueprint, 200 );
    }

    public function get_mapping( WP_REST_Request $request ): WP_REST_Response {
        $slug = sanitize_key( $request['slug'] );
        $mapping = ContentHub_Template_Mapper::instance()->get_mapping( $slug );
        return new WP_REST_Response( $mapping, 200 );
    }

    public function save_mapping( WP_REST_Request $request ): WP_REST_Response {
        $slug    = sanitize_key( $request['slug'] );
        $mapping = $request->get_json_params();

        if ( ! is_array( $mapping ) ) {
            return new WP_REST_Response( [ 'error' => 'Invalid mapping data.' ], 400 );
        }

        ContentHub_Template_Mapper::instance()->save_mapping( $slug, $mapping );
        return new WP_REST_Response( [ 'success' => true ], 200 );
    }

    // -------------------------------------------------------------------------
    // Batch Deploy
    // -------------------------------------------------------------------------

    public function deploy_batch( WP_REST_Request $request ): WP_REST_Response {
        $slug = sanitize_key( $request['slug'] );
        $results = ContentHub_Content_Deployer::instance()->deploy_batch( $slug );
        return new WP_REST_Response( $results, 200 );
    }

    // -------------------------------------------------------------------------
    // Business Profile
    // -------------------------------------------------------------------------

    public function get_business_profile( WP_REST_Request $request ): WP_REST_Response {
        $profile = ContentHub_Business_Profile::instance();
        return new WP_REST_Response( [
            'data'   => $profile->get(),
            'schema' => $profile->get_schema(),
        ], 200 );
    }

    public function save_business_profile( WP_REST_Request $request ): WP_REST_Response {
        $data = $request->get_json_params();
        ContentHub_Business_Profile::instance()->save( $data );
        return new WP_REST_Response( [ 'success' => true ], 200 );
    }

    // -------------------------------------------------------------------------
    // Settings
    // -------------------------------------------------------------------------

    public function get_settings( WP_REST_Request $request ): WP_REST_Response {
        $settings_mgr = ContentHub_Settings::instance();
        return new WP_REST_Response( [
            'has_api_key' => $settings_mgr->has_api_key(),
            'settings'    => $settings_mgr->get_settings(),
        ], 200 );
    }

    public function save_settings( WP_REST_Request $request ): WP_REST_Response {
        $data = $request->get_json_params();
        $settings_mgr = ContentHub_Settings::instance();

        // Handle API key separately.
        if ( isset( $data['api_key'] ) ) {
            $settings_mgr->set_api_key( $data['api_key'] );
            unset( $data['api_key'] );
        }

        if ( ! empty( $data ) ) {
            $settings_mgr->save_settings( $data );
        }

        return new WP_REST_Response( [
            'success'     => true,
            'has_api_key' => $settings_mgr->has_api_key(),
        ], 200 );
    }

    // -------------------------------------------------------------------------
    // Share Token
    // -------------------------------------------------------------------------

    public function get_share_token( WP_REST_Request $request ): WP_REST_Response {
        $token_mgr = ContentHub_Share_Token::instance();
        $data = $token_mgr->get();

        return new WP_REST_Response( [
            'active'    => $data !== null,
            'url'       => $token_mgr->get_share_url(),
            'created_at' => $data['created_at'] ?? null,
        ], 200 );
    }

    public function generate_share_token( WP_REST_Request $request ): WP_REST_Response {
        $token_mgr = ContentHub_Share_Token::instance();
        $token_mgr->generate();

        return new WP_REST_Response( [
            'active' => true,
            'url'    => $token_mgr->get_share_url(),
        ], 200 );
    }

    public function revoke_share_token( WP_REST_Request $request ): WP_REST_Response {
        ContentHub_Share_Token::instance()->revoke();

        return new WP_REST_Response( [
            'active' => false,
            'url'    => null,
        ], 200 );
    }

    // -------------------------------------------------------------------------
    // Public Business Profile (token-authenticated)
    // -------------------------------------------------------------------------

    public function check_share_token( WP_REST_Request $request ): bool {
        $token = $request->get_header( 'X-Share-Token' );
        if ( empty( $token ) ) {
            $token = $request->get_param( 'share_token' );
        }

        if ( empty( $token ) ) {
            return false;
        }

        return ContentHub_Share_Token::instance()->validate( $token );
    }

    public function public_get_business_profile( WP_REST_Request $request ): WP_REST_Response {
        $profile = ContentHub_Business_Profile::instance();
        return new WP_REST_Response( [
            'data'   => $profile->get(),
            'schema' => $profile->get_schema(),
        ], 200 );
    }

    public function public_save_business_profile( WP_REST_Request $request ): WP_REST_Response {
        $data = $request->get_json_params();
        ContentHub_Business_Profile::instance()->save( $data );
        return new WP_REST_Response( [ 'success' => true ], 200 );
    }

    // -------------------------------------------------------------------------
    // Portal Visibility (admin)
    // -------------------------------------------------------------------------

    public function get_portal_visibility( WP_REST_Request $request ): WP_REST_Response {
        $settings = ContentHub_Settings::instance()->get_settings();
        return new WP_REST_Response( [
            'page_ids' => $settings['portal_visible_pages'] ?? [],
        ], 200 );
    }

    public function save_portal_visibility( WP_REST_Request $request ): WP_REST_Response {
        $data     = $request->get_json_params();
        $page_ids = array_map( 'absint', $data['page_ids'] ?? [] );

        $settings_mgr = ContentHub_Settings::instance();
        $settings     = $settings_mgr->get_settings();
        $settings['portal_visible_pages'] = $page_ids;
        $settings_mgr->save_settings( $settings );

        return new WP_REST_Response( [ 'success' => true, 'page_ids' => $page_ids ], 200 );
    }

    // -------------------------------------------------------------------------
    // Public Portal Endpoints (token-authenticated)
    // -------------------------------------------------------------------------

    private function is_page_portal_visible( int $page_id ): bool {
        $settings    = ContentHub_Settings::instance()->get_settings();
        $visible_ids = $settings['portal_visible_pages'] ?? [];
        return in_array( $page_id, array_map( 'intval', $visible_ids ), true );
    }

    public function public_get_portal_config( WP_REST_Request $request ): WP_REST_Response {
        return new WP_REST_Response( [
            'site_name'     => get_bloginfo( 'name' ),
            'site_icon_url' => get_site_icon_url( 64 ),
            'ai_available'  => ContentHub_Settings::instance()->has_api_key(),
        ], 200 );
    }

    public function public_get_pages( WP_REST_Request $request ): WP_REST_Response {
        $settings    = ContentHub_Settings::instance()->get_settings();
        $visible_ids = array_map( 'intval', $settings['portal_visible_pages'] ?? [] );

        if ( empty( $visible_ids ) ) {
            return new WP_REST_Response( [], 200 );
        }

        $all_pages = ContentHub_Page_Discovery::instance()->get_all_pages();
        $filtered  = array_values( array_filter( $all_pages, function ( $page ) use ( $visible_ids ) {
            return in_array( (int) $page['id'], $visible_ids, true );
        } ) );

        return new WP_REST_Response( $filtered, 200 );
    }

    public function public_get_page_content( WP_REST_Request $request ): WP_REST_Response {
        $page_id = (int) $request['id'];
        if ( ! $this->is_page_portal_visible( $page_id ) ) {
            return new WP_REST_Response( [ 'error' => 'Page not available.' ], 403 );
        }
        return $this->get_page_content( $request );
    }

    public function public_save_page_content( WP_REST_Request $request ): WP_REST_Response {
        $page_id = (int) $request['id'];
        if ( ! $this->is_page_portal_visible( $page_id ) ) {
            return new WP_REST_Response( [ 'error' => 'Page not available.' ], 403 );
        }
        return $this->save_page_content( $request );
    }

    public function public_get_mapping( WP_REST_Request $request ): WP_REST_Response {
        return $this->get_mapping( $request );
    }

    public function public_get_page_context( WP_REST_Request $request ): WP_REST_Response {
        $page_id = (int) $request['id'];
        if ( ! $this->is_page_portal_visible( $page_id ) ) {
            return new WP_REST_Response( [ 'error' => 'Page not available.' ], 403 );
        }
        return $this->get_page_context( $request );
    }

    public function public_save_page_context( WP_REST_Request $request ): WP_REST_Response {
        $page_id = (int) $request['id'];
        if ( ! $this->is_page_portal_visible( $page_id ) ) {
            return new WP_REST_Response( [ 'error' => 'Page not available.' ], 403 );
        }
        return $this->save_page_context( $request );
    }

    public function public_scrape_page( WP_REST_Request $request ): WP_REST_Response {
        $page_id = (int) $request['id'];
        if ( ! $this->is_page_portal_visible( $page_id ) ) {
            return new WP_REST_Response( [ 'error' => 'Page not available.' ], 403 );
        }
        return $this->scrape_page( $request );
    }

    public function public_generate_page_content( WP_REST_Request $request ): WP_REST_Response {
        $page_id = (int) $request['id'];
        if ( ! $this->is_page_portal_visible( $page_id ) ) {
            return new WP_REST_Response( [ 'error' => 'Page not available.' ], 403 );
        }
        if ( ! ContentHub_Settings::instance()->has_api_key() ) {
            return new WP_REST_Response( [ 'error' => 'AI generation is not available.' ], 400 );
        }
        return $this->generate_page_content( $request );
    }

    public function public_preview_page( WP_REST_Request $request ): WP_REST_Response {
        $page_id = (int) $request['id'];
        if ( ! $this->is_page_portal_visible( $page_id ) ) {
            return new WP_REST_Response( [ 'error' => 'Page not available.' ], 403 );
        }

        $data    = $request->get_json_params();
        $content = $data['data'] ?? $data;

        // Use a portal-specific transient key (no user_id since not logged in).
        $token         = $request->get_header( 'X-Share-Token' ) ?: $request->get_param( 'share_token' );
        $token_hash    = substr( md5( $token ), 0, 8 );
        $transient_key = "contenthub_preview_{$page_id}_portal_{$token_hash}";
        set_transient( $transient_key, wp_json_encode( $content ), 300 );

        $sig = hash_hmac( 'sha256', "{$page_id}:{$token_hash}", wp_salt( 'nonce' ) );
        $url = add_query_arg( [
            'contenthub_portal_preview' => $page_id,
            '_sig'                      => $sig,
            '_th'                       => $token_hash,
        ], home_url( '/' ) );

        return new WP_REST_Response( [ 'url' => $url ], 200 );
    }
}
