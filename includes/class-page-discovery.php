<?php
/**
 * Discovers WordPress pages and manages template type assignments.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ContentHub_Page_Discovery {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get all published pages with their ContentHub metadata.
     */
    public function get_all_pages(): array {
        $pages = get_pages( [
            'post_status' => 'publish',
            'sort_column' => 'menu_order,post_title',
        ] );

        $result = [];
        foreach ( $pages as $page ) {
            $result[] = $this->format_page( $page );
        }

        return $result;
    }

    /**
     * Get a single page with ContentHub metadata.
     */
    public function get_page( int $page_id ): ?array {
        $page = get_post( $page_id );
        if ( ! $page || 'page' !== $page->post_type ) {
            return null;
        }
        return $this->format_page( $page );
    }

    /**
     * Get pages assigned to a specific template type.
     */
    public function get_pages_by_template( string $template_slug ): array {
        $query = new WP_Query( [
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_key'       => '_contenthub_template_type',
            'meta_value'     => $template_slug,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ] );

        $result = [];
        foreach ( $query->posts as $page ) {
            $result[] = $this->format_page( $page );
        }

        return $result;
    }

    /**
     * Assign a template type to a page.
     */
    public function assign_template( int $page_id, string $template_slug ): bool {
        if ( empty( $template_slug ) ) {
            return delete_post_meta( $page_id, '_contenthub_template_type' );
        }
        return (bool) update_post_meta( $page_id, '_contenthub_template_type', sanitize_key( $template_slug ) );
    }

    /**
     * Get the template type assigned to a page.
     */
    public function get_template_type( int $page_id ): string {
        return (string) get_post_meta( $page_id, '_contenthub_template_type', true );
    }

    /**
     * Get the content status of a page.
     */
    public function get_content_status( int $page_id ): string {
        return (string) get_post_meta( $page_id, '_contenthub_content_status', true );
    }

    /**
     * Get the content source of a page.
     */
    public function get_content_source( int $page_id ): string {
        return (string) get_post_meta( $page_id, '_contenthub_content_source', true );
    }

    /**
     * Format a WP_Post into our standard page array.
     */
    private function format_page( WP_Post $page ): array {
        $page_id = $page->ID;
        return [
            'id'               => $page_id,
            'title'            => $page->post_title,
            'url'              => get_permalink( $page_id ),
            'parent_id'        => $page->post_parent,
            'menu_order'       => $page->menu_order,
            'template_type'    => $this->get_template_type( $page_id ),
            'content_status'   => $this->get_content_status( $page_id ),
            'content_source'   => $this->get_content_source( $page_id ),
            'has_elementor'    => (bool) get_post_meta( $page_id, '_elementor_edit_mode', true ),
            'deployed_at'      => get_post_meta( $page_id, '_contenthub_deployed_at', true ) ?: null,
        ];
    }
}
