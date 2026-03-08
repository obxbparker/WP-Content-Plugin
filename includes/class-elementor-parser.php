<?php
/**
 * Parses Elementor page data (_elementor_data) into content blueprints.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ContentHub_Elementor_Parser {

    private static $instance = null;

    /**
     * Widget types and their content-bearing setting keys.
     * Each entry maps widgetType => [ setting_key => description ].
     */
    private const WIDGET_CONTENT_MAP = [
        'heading'          => [
            'title' => 'Heading text',
        ],
        'text-editor'      => [
            'editor' => 'Rich text content',
        ],
        'icon-box'         => [
            'title_text'       => 'Icon box title',
            'description_text' => 'Icon box description',
        ],
        'image-box'        => [
            'title_text'       => 'Image box title',
            'description_text' => 'Image box description',
        ],
        'accordion'        => [
            'tabs' => 'Accordion items (tab_title, tab_content)',
        ],
        'toggle'           => [
            'tabs' => 'Toggle items (tab_title, tab_content)',
        ],
        'button'           => [
            'text' => 'Button text',
        ],
        'call-to-action'   => [
            'title'       => 'CTA title',
            'description' => 'CTA description',
            'button_text' => 'CTA button text',
        ],
        'testimonial'      => [
            'testimonial_content' => 'Testimonial text',
            'testimonial_name'    => 'Author name',
            'testimonial_job'     => 'Author job title',
        ],
        'icon-list'        => [
            'icon_list' => 'List items (text)',
        ],
        'counter'          => [
            'title' => 'Counter label',
        ],
        'price-table'      => [
            'heading'     => 'Price table heading',
            'sub_heading' => 'Price table subheading',
            'price'       => 'Price value',
            'features_list' => 'Feature list items',
        ],
        'tabs'             => [
            'tabs' => 'Tab items (tab_title, tab_content)',
        ],
    ];

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get raw Elementor data for a page.
     */
    public function get_elementor_data( int $page_id ): ?array {
        $raw = get_post_meta( $page_id, '_elementor_data', true );
        if ( empty( $raw ) ) {
            return null;
        }

        $data = is_string( $raw ) ? json_decode( $raw, true ) : $raw;
        return is_array( $data ) ? $data : null;
    }

    /**
     * Build a content blueprint from an Elementor page.
     *
     * Returns an ordered array of content slots describing every
     * content-bearing widget on the page.
     */
    public function build_blueprint( int $page_id ): ?array {
        $data = $this->get_elementor_data( $page_id );
        if ( null === $data ) {
            return null;
        }

        $slots = [];
        $this->walk_elements( $data, $slots, [] );
        return $slots;
    }

    /**
     * Recursively walk the Elementor element tree.
     *
     * @param array $elements   Array of Elementor elements.
     * @param array &$slots     Accumulator for content slots.
     * @param array $path       Current path in the tree (for context).
     */
    private function walk_elements( array $elements, array &$slots, array $path ): void {
        foreach ( $elements as $index => $element ) {
            $el_type = $element['elType'] ?? '';
            $current_path = array_merge( $path, [ "{$el_type}:{$index}" ] );

            if ( 'widget' === $el_type ) {
                $widget_type = $element['widgetType'] ?? '';
                $widget_slots = $this->extract_widget_slots( $element, $current_path, count( $slots ) );
                $slots = array_merge( $slots, $widget_slots );
            }

            // Recurse into child elements (sections, columns, containers).
            if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
                $this->walk_elements( $element['elements'], $slots, $current_path );
            }
        }
    }

    /**
     * Extract content slots from a single widget.
     */
    private function extract_widget_slots( array $widget, array $path, int $position_offset ): array {
        $widget_type = $widget['widgetType'] ?? '';
        $widget_id   = $widget['id'] ?? '';
        $settings    = $widget['settings'] ?? [];

        if ( ! isset( self::WIDGET_CONTENT_MAP[ $widget_type ] ) ) {
            return [];
        }

        $content_keys = self::WIDGET_CONTENT_MAP[ $widget_type ];
        $slots = [];

        foreach ( $content_keys as $key => $description ) {
            // Handle repeater fields (tabs, icon_list, features_list).
            if ( in_array( $key, [ 'tabs', 'icon_list', 'features_list' ], true ) ) {
                $slots[] = [
                    'slot_id'      => $widget_id,
                    'widget_type'  => $widget_type,
                    'field'        => $key,
                    'field_type'   => 'repeater',
                    'description'  => $description,
                    'position'     => $position_offset + count( $slots ),
                    'path'         => implode( ' > ', $path ),
                    'current_value' => $this->get_repeater_preview( $settings, $key, $widget_type ),
                ];
            } else {
                $slots[] = [
                    'slot_id'      => $widget_id,
                    'widget_type'  => $widget_type,
                    'field'        => $key,
                    'field_type'   => 'text',
                    'description'  => $description,
                    'position'     => $position_offset + count( $slots ),
                    'path'         => implode( ' > ', $path ),
                    'current_value' => $this->truncate( $settings[ $key ] ?? '', 100 ),
                ];
            }
        }

        return $slots;
    }

    /**
     * Get a preview of repeater field content.
     */
    private function get_repeater_preview( array $settings, string $key, string $widget_type ): array {
        $items = $settings[ $key ] ?? [];
        if ( ! is_array( $items ) ) {
            return [];
        }

        $preview = [];
        foreach ( array_slice( $items, 0, 5 ) as $item ) {
            if ( in_array( $widget_type, [ 'accordion', 'toggle', 'tabs' ], true ) ) {
                $preview[] = [
                    'title'   => $this->truncate( $item['tab_title'] ?? '', 60 ),
                    'content' => $this->truncate( wp_strip_all_tags( $item['tab_content'] ?? '' ), 100 ),
                ];
            } elseif ( 'icon-list' === $widget_type ) {
                $preview[] = [
                    'text' => $this->truncate( $item['text'] ?? '', 80 ),
                ];
            } elseif ( 'price-table' === $widget_type && 'features_list' === $key ) {
                $preview[] = [
                    'text' => $this->truncate( $item['item_text'] ?? '', 80 ),
                ];
            }
        }

        return $preview;
    }

    /**
     * Get the list of recognized widget types.
     */
    public function get_recognized_widget_types(): array {
        return array_keys( self::WIDGET_CONTENT_MAP );
    }

    /**
     * Get the content setting keys for a widget type.
     */
    public function get_widget_content_keys( string $widget_type ): array {
        return self::WIDGET_CONTENT_MAP[ $widget_type ] ?? [];
    }

    /**
     * Truncate a string for preview purposes.
     */
    private function truncate( string $text, int $max_length ): string {
        $text = wp_strip_all_tags( $text );
        if ( mb_strlen( $text ) <= $max_length ) {
            return $text;
        }
        return mb_substr( $text, 0, $max_length ) . '…';
    }
}
