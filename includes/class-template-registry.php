<?php
/**
 * User-defined template type registry.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ContentHub_Template_Registry {

    private static $instance = null;
    private const OPTION_KEY = 'contenthub_template_types';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get all registered template types.
     *
     * @return array Array of {slug, name, elementor_template_id}
     */
    public function get_all(): array {
        $raw = get_option( self::OPTION_KEY, '[]' );
        $types = json_decode( $raw, true );
        if ( ! is_array( $types ) ) {
            return [];
        }

        // Migrate legacy field name.
        $migrated = false;
        foreach ( $types as &$type ) {
            if ( isset( $type['example_page_id'] ) && ! isset( $type['elementor_template_id'] ) ) {
                $type['elementor_template_id'] = $type['example_page_id'];
                unset( $type['example_page_id'] );
                $migrated = true;
            }
        }
        if ( $migrated ) {
            $this->save( $types );
        }

        return $types;
    }

    /**
     * Get a single template type by slug.
     */
    public function get( string $slug ): ?array {
        $types = $this->get_all();
        foreach ( $types as $type ) {
            if ( $type['slug'] === $slug ) {
                return $type;
            }
        }
        return null;
    }

    /**
     * Create a new template type.
     */
    public function create( string $name ): array {
        $types = $this->get_all();
        $slug  = $this->generate_slug( $name, $types );

        $new_type = [
            'slug'                  => $slug,
            'name'                  => sanitize_text_field( $name ),
            'elementor_template_id' => 0,
        ];

        $types[] = $new_type;
        $this->save( $types );

        return $new_type;
    }

    /**
     * Update a template type.
     */
    public function update( string $slug, array $data ): ?array {
        $types = $this->get_all();

        foreach ( $types as &$type ) {
            if ( $type['slug'] === $slug ) {
                if ( isset( $data['name'] ) ) {
                    $type['name'] = sanitize_text_field( $data['name'] );
                }
                if ( isset( $data['elementor_template_id'] ) ) {
                    $type['elementor_template_id'] = absint( $data['elementor_template_id'] );
                }
                $this->save( $types );
                return $type;
            }
        }

        return null;
    }

    /**
     * Delete a template type.
     */
    public function delete( string $slug ): bool {
        $types = $this->get_all();
        $filtered = array_values( array_filter( $types, function ( $t ) use ( $slug ) {
            return $t['slug'] !== $slug;
        } ) );

        if ( count( $filtered ) === count( $types ) ) {
            return false;
        }

        $this->save( $filtered );

        // Clean up the mapping option for this type.
        delete_option( "contenthub_template_mapping_{$slug}" );

        // Remove template assignments from pages.
        global $wpdb;
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_contenthub_template_type' AND meta_value = %s",
            $slug
        ) );

        return true;
    }

    /**
     * Set the Elementor Library template for a template type.
     */
    public function set_elementor_template( string $slug, int $template_id ): ?array {
        return $this->update( $slug, [ 'elementor_template_id' => $template_id ] );
    }

    /**
     * Generate a unique slug from a name.
     */
    private function generate_slug( string $name, array $existing_types ): string {
        $slug = sanitize_title( $name );
        $existing_slugs = array_column( $existing_types, 'slug' );

        if ( ! in_array( $slug, $existing_slugs, true ) ) {
            return $slug;
        }

        $i = 2;
        while ( in_array( "{$slug}-{$i}", $existing_slugs, true ) ) {
            $i++;
        }
        return "{$slug}-{$i}";
    }

    private function save( array $types ): bool {
        return update_option( self::OPTION_KEY, wp_json_encode( $types ) );
    }
}
