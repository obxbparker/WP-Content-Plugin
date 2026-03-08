<?php
/**
 * Maps content fields to Elementor widget content slots.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ContentHub_Template_Mapper {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the saved mapping for a template type.
     */
    public function get_mapping( string $template_slug ): array {
        $raw = get_option( "contenthub_template_mapping_{$template_slug}", '[]' );
        $mapping = json_decode( $raw, true );
        return is_array( $mapping ) ? $mapping : [];
    }

    /**
     * Save a mapping for a template type.
     *
     * @param string $template_slug Template type slug.
     * @param array  $mapping       Array of {slot_id, field, content_field_name, content_field_type}.
     */
    public function save_mapping( string $template_slug, array $mapping ): bool {
        $sanitized = array_map( function ( $entry ) {
            return [
                'slot_id'             => sanitize_text_field( $entry['slot_id'] ?? '' ),
                'widget_type'         => sanitize_text_field( $entry['widget_type'] ?? '' ),
                'field'               => sanitize_text_field( $entry['field'] ?? '' ),
                'content_field_name'  => sanitize_text_field( $entry['content_field_name'] ?? '' ),
                'content_field_type'  => sanitize_text_field( $entry['content_field_type'] ?? 'text' ),
                'position'            => absint( $entry['position'] ?? 0 ),
            ];
        }, $mapping );

        return update_option( "contenthub_template_mapping_{$template_slug}", wp_json_encode( $sanitized ) );
    }

    /**
     * Auto-generate an initial mapping from a blueprint.
     *
     * Uses heuristics to assign semantic content field names
     * based on widget type and position.
     */
    public function auto_map( array $blueprint ): array {
        $mapping = [];
        $heading_count = 0;
        $text_count = 0;
        $repeater_groups = $this->detect_repeater_groups( $blueprint );

        foreach ( $blueprint as $slot ) {
            $field_name = '';
            $field_type = $slot['field_type'] ?? 'text';

            switch ( $slot['widget_type'] ) {
                case 'heading':
                    $heading_count++;
                    if ( 1 === $heading_count ) {
                        $field_name = 'hero_heading';
                    } else {
                        $field_name = "section_heading_{$heading_count}";
                    }
                    break;

                case 'text-editor':
                    $text_count++;
                    if ( 1 === $text_count ) {
                        $field_name = 'hero_description';
                    } elseif ( 2 === $text_count ) {
                        $field_name = 'about_content';
                    } else {
                        $field_name = "content_block_{$text_count}";
                    }
                    break;

                case 'icon-box':
                case 'image-box':
                    $group = $this->find_group_for_slot( $slot, $repeater_groups );
                    if ( $group ) {
                        $field_name = $group['name'] . '_' . $slot['field'];
                    } else {
                        $field_name = $slot['widget_type'] . '_' . $slot['field'] . '_' . $slot['position'];
                    }
                    $field_type = 'group_item';
                    break;

                case 'accordion':
                case 'toggle':
                    $field_name = 'faqs';
                    $field_type = 'repeater';
                    break;

                case 'tabs':
                    $field_name = 'content_tabs';
                    $field_type = 'repeater';
                    break;

                case 'button':
                    $field_name = 'call_to_action';
                    break;

                case 'call-to-action':
                    if ( 'title' === $slot['field'] ) {
                        $field_name = 'cta_title';
                    } elseif ( 'description' === $slot['field'] ) {
                        $field_name = 'cta_description';
                    } else {
                        $field_name = 'cta_button_text';
                    }
                    break;

                case 'testimonial':
                    if ( 'testimonial_content' === $slot['field'] ) {
                        $field_name = 'testimonial_content';
                    } elseif ( 'testimonial_name' === $slot['field'] ) {
                        $field_name = 'testimonial_author';
                    } else {
                        $field_name = 'testimonial_job';
                    }
                    $field_type = 'group_item';
                    break;

                case 'icon-list':
                    $field_name = 'feature_list';
                    $field_type = 'repeater';
                    break;

                case 'counter':
                    $field_name = 'counter_label_' . $slot['position'];
                    break;

                default:
                    $field_name = $slot['widget_type'] . '_' . $slot['field'] . '_' . $slot['position'];
                    break;
            }

            $mapping[] = [
                'slot_id'             => $slot['slot_id'],
                'widget_type'         => $slot['widget_type'],
                'field'               => $slot['field'],
                'content_field_name'  => $field_name,
                'content_field_type'  => $field_type,
                'position'            => $slot['position'],
            ];
        }

        return $mapping;
    }

    /**
     * Apply content data to an Elementor data structure using a mapping.
     *
     * @param array $elementor_data Full Elementor JSON structure (deep-cloned).
     * @param array $mapping        The field mapping.
     * @param array $content_data   The content data keyed by content_field_name.
     * @return array Modified Elementor data.
     */
    public function apply_content( array $elementor_data, array $mapping, array $content_data ): array {
        // Build a lookup: widget_id => [ { field, content_field_name, content_field_type } ]
        $widget_map = [];
        foreach ( $mapping as $entry ) {
            $widget_map[ $entry['slot_id'] ][] = $entry;
        }

        return $this->walk_and_apply( $elementor_data, $widget_map, $content_data );
    }

    /**
     * Recursively walk elements and apply content to mapped widgets.
     */
    private function walk_and_apply( array $elements, array $widget_map, array $content_data ): array {
        foreach ( $elements as &$element ) {
            $el_id = $element['id'] ?? '';

            if ( 'widget' === ( $element['elType'] ?? '' ) && isset( $widget_map[ $el_id ] ) ) {
                foreach ( $widget_map[ $el_id ] as $entry ) {
                    $content_field = $entry['content_field_name'];
                    $widget_field  = $entry['field'];

                    if ( ! isset( $content_data[ $content_field ] ) ) {
                        continue;
                    }

                    $value = $content_data[ $content_field ];

                    if ( 'repeater' === $entry['content_field_type'] && is_array( $value ) ) {
                        $element['settings'][ $widget_field ] = $this->build_repeater_value(
                            $element['settings'][ $widget_field ] ?? [],
                            $value,
                            $element['widgetType'] ?? ''
                        );
                    } else {
                        $element['settings'][ $widget_field ] = $value;
                    }
                }
            }

            // Recurse.
            if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
                $element['elements'] = $this->walk_and_apply( $element['elements'], $widget_map, $content_data );
            }
        }

        return $elements;
    }

    /**
     * Build repeater field values for accordion/toggle/tabs/icon-list.
     */
    private function build_repeater_value( array $existing_items, array $new_items, string $widget_type ): array {
        $result = [];
        $template_item = ! empty( $existing_items[0] ) ? $existing_items[0] : [];

        foreach ( $new_items as $i => $item ) {
            $new_entry = $template_item; // Clone styling/settings from template.
            $new_entry['_id'] = $this->generate_element_id();

            if ( in_array( $widget_type, [ 'accordion', 'toggle', 'tabs' ], true ) ) {
                $new_entry['tab_title']   = $item['title'] ?? $item['tab_title'] ?? '';
                $new_entry['tab_content'] = $item['content'] ?? $item['tab_content'] ?? '';
            } elseif ( 'icon-list' === $widget_type ) {
                $new_entry['text'] = $item['text'] ?? $item ?? '';
            } elseif ( 'price-table' === $widget_type ) {
                $new_entry['item_text'] = $item['text'] ?? $item ?? '';
            }

            $result[] = $new_entry;
        }

        return $result;
    }

    /**
     * Detect groups of similar adjacent widgets (e.g., multiple icon-boxes in a row).
     */
    private function detect_repeater_groups( array $blueprint ): array {
        $groups = [];
        $current_group = [];
        $current_type = '';
        $group_index = 0;

        foreach ( $blueprint as $slot ) {
            $widget_type = $slot['widget_type'];

            if ( in_array( $widget_type, [ 'icon-box', 'image-box', 'testimonial' ], true ) ) {
                if ( $widget_type === $current_type ) {
                    $current_group[] = $slot;
                } else {
                    if ( count( $current_group ) >= 2 ) {
                        $group_index++;
                        $groups[] = [
                            'name'  => $this->group_name( $current_type, $group_index ),
                            'type'  => $current_type,
                            'slots' => $current_group,
                        ];
                    }
                    $current_type = $widget_type;
                    $current_group = [ $slot ];
                }
            } else {
                if ( count( $current_group ) >= 2 ) {
                    $group_index++;
                    $groups[] = [
                        'name'  => $this->group_name( $current_type, $group_index ),
                        'type'  => $current_type,
                        'slots' => $current_group,
                    ];
                }
                $current_type = '';
                $current_group = [];
            }
        }

        // Don't forget the last group.
        if ( count( $current_group ) >= 2 ) {
            $group_index++;
            $groups[] = [
                'name'  => $this->group_name( $current_type, $group_index ),
                'type'  => $current_type,
                'slots' => $current_group,
            ];
        }

        return $groups;
    }

    private function group_name( string $widget_type, int $index ): string {
        $names = [
            'icon-box'    => 'services',
            'image-box'   => 'features',
            'testimonial' => 'testimonials',
        ];
        $base = $names[ $widget_type ] ?? 'items';
        return 1 === $index ? $base : "{$base}_{$index}";
    }

    private function find_group_for_slot( array $slot, array $groups ): ?array {
        foreach ( $groups as $group ) {
            foreach ( $group['slots'] as $group_slot ) {
                if ( $group_slot['slot_id'] === $slot['slot_id'] && $group_slot['field'] === $slot['field'] ) {
                    return $group;
                }
            }
        }
        return null;
    }

    /**
     * Generate a random Elementor-compatible element ID.
     */
    private function generate_element_id(): string {
        return substr( md5( wp_generate_uuid4() ), 0, 7 );
    }
}
