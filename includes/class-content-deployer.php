<?php
/**
 * Deploys content to pages by writing Elementor-compatible JSON.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ContentHub_Content_Deployer {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Deploy content to a single page.
     *
     * @param int    $page_id        Target page ID.
     * @param array  $content_data   Structured content keyed by field name.
     * @param string $template_slug  Template type slug.
     * @return true|WP_Error
     */
    public function deploy( int $page_id, array $content_data, string $template_slug ) {
        // Get the template type and its Elementor Library template.
        $registry = ContentHub_Template_Registry::instance();
        $template = $registry->get( $template_slug );

        if ( ! $template ) {
            return new WP_Error( 'invalid_template', "Template type '{$template_slug}' not found." );
        }

        $template_id = $template['elementor_template_id'] ?? 0;
        if ( ! $template_id ) {
            return new WP_Error( 'no_template', "No Elementor template assigned for template type '{$template_slug}'." );
        }

        // Get the Elementor template's data.
        $parser = ContentHub_Elementor_Parser::instance();
        $elementor_data = $parser->get_elementor_data( $template_id );

        if ( null === $elementor_data ) {
            return new WP_Error( 'no_elementor_data', 'Could not read Elementor data from the assigned template.' );
        }

        // Get the field mapping.
        $mapper = ContentHub_Template_Mapper::instance();
        $mapping = $mapper->get_mapping( $template_slug );

        if ( empty( $mapping ) ) {
            return new WP_Error( 'no_mapping', "No field mapping configured for template type '{$template_slug}'." );
        }

        // Backup current Elementor data if settings say so.
        $settings = ContentHub_Settings::instance()->get_settings();
        if ( ! empty( $settings['backup_before_deploy'] ) ) {
            $this->backup( $page_id );
        }

        // Deep-clone the example page's data.
        $cloned_data = $this->deep_clone( $elementor_data );

        // Regenerate all element IDs to be unique for this page.
        $cloned_data = $this->regenerate_ids( $cloned_data );

        // Apply content using the mapper.
        $final_data = $mapper->apply_content( $cloned_data, $mapping, $content_data );

        // Write to the target page.
        $result = $this->write_elementor_data( $page_id, $final_data );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        // Update content status.
        update_post_meta( $page_id, '_contenthub_content_status', 'deployed' );
        update_post_meta( $page_id, '_contenthub_deployed_at', current_time( 'mysql' ) );

        // Clear Elementor cache for this page.
        $this->clear_elementor_cache( $page_id );

        return true;
    }

    /**
     * Deploy content to all pages of a template type.
     *
     * @return array { success: int[], failed: [{id, error}] }
     */
    public function deploy_batch( string $template_slug ): array {
        $discovery = ContentHub_Page_Discovery::instance();
        $pages = $discovery->get_pages_by_template( $template_slug );

        $results = [
            'success' => [],
            'failed'  => [],
        ];

        foreach ( $pages as $page ) {
            $page_id = $page['id'];

            // No need to skip — Elementor Library templates are separate
            // from regular pages, so there's no risk of overwriting.

            // Get the page's content data.
            $content_data = get_post_meta( $page_id, '_contenthub_content_data', true );
            if ( empty( $content_data ) ) {
                $results['failed'][] = [
                    'id'    => $page_id,
                    'title' => $page['title'],
                    'error' => 'No content data available.',
                ];
                continue;
            }

            $content = json_decode( $content_data, true );
            if ( ! is_array( $content ) ) {
                $results['failed'][] = [
                    'id'    => $page_id,
                    'title' => $page['title'],
                    'error' => 'Invalid content data format.',
                ];
                continue;
            }

            $deploy_result = $this->deploy( $page_id, $content, $template_slug );

            if ( is_wp_error( $deploy_result ) ) {
                $results['failed'][] = [
                    'id'    => $page_id,
                    'title' => $page['title'],
                    'error' => $deploy_result->get_error_message(),
                ];
            } else {
                $results['success'][] = $page_id;
            }
        }

        return $results;
    }

    /**
     * Rollback a page to its pre-deployment Elementor data.
     */
    public function rollback( int $page_id ) {
        $backup = get_post_meta( $page_id, '_contenthub_backup_elementor_data', true );
        if ( empty( $backup ) ) {
            return new WP_Error( 'no_backup', 'No backup data found for this page.' );
        }

        $result = update_post_meta( $page_id, '_elementor_data', $backup );
        if ( false === $result ) {
            return new WP_Error( 'rollback_failed', 'Failed to restore Elementor data.' );
        }

        // Update status.
        update_post_meta( $page_id, '_contenthub_content_status', 'ready' );
        delete_post_meta( $page_id, '_contenthub_deployed_at' );

        // Clear cache.
        $this->clear_elementor_cache( $page_id );

        return true;
    }

    /**
     * Backup the current Elementor data for a page.
     */
    private function backup( int $page_id ): void {
        $current = get_post_meta( $page_id, '_elementor_data', true );
        if ( ! empty( $current ) ) {
            update_post_meta( $page_id, '_contenthub_backup_elementor_data', $current );
        }
    }

    /**
     * Write Elementor data to a page's postmeta.
     */
    private function write_elementor_data( int $page_id, array $data ) {
        $json = wp_json_encode( $data );
        if ( false === $json ) {
            return new WP_Error( 'json_encode_failed', 'Failed to encode Elementor data as JSON.' );
        }

        update_post_meta( $page_id, '_elementor_data', wp_slash( $json ) );
        update_post_meta( $page_id, '_elementor_edit_mode', 'builder' );

        // Set Elementor version if available.
        if ( defined( 'ELEMENTOR_VERSION' ) ) {
            update_post_meta( $page_id, '_elementor_version', ELEMENTOR_VERSION );
        }

        return true;
    }

    /**
     * Deep-clone an array (Elementor data structure).
     */
    private function deep_clone( array $data ): array {
        return json_decode( wp_json_encode( $data ), true );
    }

    /**
     * Regenerate all element IDs in the Elementor data tree.
     */
    private function regenerate_ids( array $elements ): array {
        foreach ( $elements as &$element ) {
            if ( isset( $element['id'] ) ) {
                $element['id'] = $this->generate_element_id();
            }

            if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
                $element['elements'] = $this->regenerate_ids( $element['elements'] );
            }
        }

        return $elements;
    }

    /**
     * Generate a random Elementor-compatible element ID (7 hex chars).
     */
    private function generate_element_id(): string {
        return substr( md5( wp_generate_uuid4() ), 0, 7 );
    }

    /**
     * Clear Elementor's CSS cache for a specific page.
     */
    private function clear_elementor_cache( int $page_id ): void {
        // Delete the compiled CSS for this page.
        delete_post_meta( $page_id, '_elementor_css' );

        // If Elementor is loaded, use its cache manager.
        if ( class_exists( '\Elementor\Plugin' ) ) {
            $elementor = \Elementor\Plugin::$instance;
            if ( isset( $elementor->files_manager ) ) {
                $elementor->files_manager->clear_cache();
            }
        }
    }
}
