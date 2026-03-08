<?php
/**
 * Business profile data management.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ContentHub_Business_Profile {

    private static $instance = null;
    private const OPTION_KEY = 'contenthub_business_profile';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the business profile.
     */
    public function get(): array {
        $raw = get_option( self::OPTION_KEY, '{}' );
        $profile = json_decode( $raw, true );
        return is_array( $profile ) ? $profile : [];
    }

    /**
     * Save the business profile.
     */
    public function save( array $profile ): bool {
        $sanitized = $this->sanitize( $profile );
        return update_option( self::OPTION_KEY, wp_json_encode( $sanitized ) );
    }

    /**
     * Get the company name from the profile.
     */
    public function get_company_name(): string {
        $profile = $this->get();
        return $profile['company_name'] ?? get_bloginfo( 'name' );
    }

    /**
     * Sanitize profile data.
     */
    private function sanitize( array $profile ): array {
        $text_fields = [
            'company_name', 'tagline', 'one_sentence', 'geography',
            'website_url', 'no_website',
            'tone', 'customer_description', 'problem_solved', 'differentiators',
            'always_include', 'topics_to_avoid',
            'brand', 'size', 'material', 'application', 'requirements', 'type',
        ];

        $sanitized = [];
        foreach ( $text_fields as $field ) {
            if ( isset( $profile[ $field ] ) ) {
                if ( 'website_url' === $field ) {
                    $sanitized[ $field ] = esc_url_raw( $profile[ $field ] );
                } elseif ( 'no_website' === $field ) {
                    $sanitized[ $field ] = (bool) $profile[ $field ];
                } else {
                    $sanitized[ $field ] = sanitize_textarea_field( $profile[ $field ] );
                }
            }
        }

        return $sanitized;
    }

    /**
     * Get the default profile structure (for form rendering).
     */
    public function get_schema(): array {
        return [
            'basics' => [
                'label'  => 'Company Basics',
                'fields' => [
                    'company_name'  => [ 'label' => 'Company Name', 'type' => 'text', 'required' => true ],
                    'website_url'   => [ 'label' => 'Current Website', 'type' => 'url', 'allow_none' => true, 'none_label' => "I don't have a website" ],
                    'tagline'       => [ 'label' => 'Tagline', 'type' => 'text' ],
                    'one_sentence'  => [ 'label' => 'One-Sentence Description', 'type' => 'textarea' ],
                    'geography'     => [ 'label' => 'Service Area / Geography', 'type' => 'text' ],
                ],
            ],
            'brand_voice' => [
                'label'  => 'Brand Voice',
                'fields' => [
                    'tone'                 => [
                        'label'   => 'Brand Tone',
                        'type'    => 'select',
                        'options' => [ 'Professional', 'Technical', 'Conversational', 'Friendly', 'Authoritative', 'Innovative' ],
                    ],
                    'customer_description' => [ 'label' => 'Target Customer', 'type' => 'textarea' ],
                    'problem_solved'       => [ 'label' => 'Problem We Solve', 'type' => 'textarea' ],
                    'differentiators'      => [ 'label' => 'What Makes Us Different', 'type' => 'textarea' ],
                ],
            ],
            'bsmart' => [
                'label'  => 'B-SMART Dimensions',
                'fields' => [
                    'brand'        => [ 'label' => 'Brand', 'type' => 'text', 'allow_na' => true ],
                    'size'         => [ 'label' => 'Size', 'type' => 'text', 'allow_na' => true ],
                    'material'     => [ 'label' => 'Material', 'type' => 'text', 'allow_na' => true ],
                    'application'  => [ 'label' => 'Application', 'type' => 'text', 'allow_na' => true ],
                    'requirements' => [ 'label' => 'Requirements', 'type' => 'text', 'allow_na' => true ],
                    'type'         => [ 'label' => 'Type', 'type' => 'text', 'allow_na' => true ],
                ],
            ],
            'preferences' => [
                'label'  => 'Content Preferences',
                'fields' => [
                    'always_include'  => [ 'label' => 'Always Include (keywords, phrases)', 'type' => 'textarea' ],
                    'topics_to_avoid' => [ 'label' => 'Topics to Avoid', 'type' => 'textarea' ],
                ],
            ],
        ];
    }
}
