<?php
/**
 * AI content generation and extraction via Claude API.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ContentHub_Content_Generator {

    private static $instance = null;
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const MODEL   = 'claude-haiku-4-5-20251001';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Generate content for a page using AI.
     *
     * @param string $page_name      Page name.
     * @param string $template_slug  Template type slug.
     * @param array  $mapping        Template field mapping.
     * @param string $company_name   Company name.
     * @param array  $business_profile Business profile data.
     * @return array|WP_Error Structured content data.
     */
    public function generate( string $page_name, string $template_slug, array $mapping, string $company_name, array $business_profile = [], array $page_context = [], string $file_content = '' ) {
        $api_key = ContentHub_Settings::instance()->get_api_key();
        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', 'Claude API key is not configured. Go to ContentHub Settings to add it.' );
        }

        $prompt = $this->build_generation_prompt( $page_name, $template_slug, $mapping, $company_name, $business_profile, $page_context, $file_content );

        return $this->call_claude( $api_key, $prompt, 0.7 );
    }

    /**
     * Extract structured content from scraped text.
     *
     * @param string $raw_content   Scraped page text.
     * @param array  $headings      Extracted headings.
     * @param string $page_name     Page name.
     * @param string $template_slug Template type slug.
     * @param array  $mapping       Template field mapping.
     * @return array|WP_Error Structured content data.
     */
    public function extract_from_scraped( string $raw_content, array $headings, string $page_name, string $template_slug, array $mapping ) {
        $api_key = ContentHub_Settings::instance()->get_api_key();
        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', 'Claude API key is not configured. Go to ContentHub Settings to add it.' );
        }

        $prompt = $this->build_extraction_prompt( $raw_content, $headings, $page_name, $template_slug, $mapping );

        return $this->call_claude( $api_key, $prompt, 0.0 );
    }

    /**
     * Call the Claude API.
     */
    private function call_claude( string $api_key, string $prompt, float $temperature ) {
        $response = wp_remote_post( self::API_URL, [
            'timeout' => 60,
            'headers' => [
                'x-api-key'         => $api_key,
                'anthropic-version'  => '2023-06-01',
                'content-type'       => 'application/json',
            ],
            'body' => wp_json_encode( [
                'model'       => self::MODEL,
                'max_tokens'  => 4000,
                'temperature' => $temperature,
                'messages'    => [
                    [
                        'role'    => 'user',
                        'content' => $prompt,
                    ],
                ],
            ] ),
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status = wp_remote_retrieve_response_code( $response );
        $body   = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status >= 400 ) {
            $error_msg = $body['error']['message'] ?? "API returned status {$status}";
            return new WP_Error( 'api_error', $error_msg );
        }

        $text = $body['content'][0]['text'] ?? '';
        if ( empty( $text ) ) {
            return new WP_Error( 'empty_response', 'Claude returned an empty response.' );
        }

        // Extract JSON from response.
        $json = $this->extract_json( $text );
        if ( null === $json ) {
            return new WP_Error( 'invalid_json', 'Could not parse JSON from Claude response.' );
        }

        return $json;
    }

    /**
     * Build the content generation prompt.
     */
    private function build_generation_prompt( string $page_name, string $template_slug, array $mapping, string $company_name, array $business_profile, array $page_context = [], string $file_content = '' ): string {
        $fields_description = $this->describe_expected_fields( $mapping );

        $prompt = "You are a professional website copywriter. Generate content for a website page.\n\n";
        $prompt .= "## Page Details\n";
        $prompt .= "- Page name: {$page_name}\n";
        $prompt .= "- Template type: {$template_slug}\n";
        $prompt .= "- Company name: {$company_name}\n";

        // Page-specific context.
        if ( ! empty( $page_context ) ) {
            if ( ! empty( $page_context['page_topic'] ) ) {
                $prompt .= "- Page topic: {$page_context['page_topic']}\n";
            }
            if ( ! empty( $page_context['target_keywords'] ) ) {
                $prompt .= "- Target keywords: {$page_context['target_keywords']}\n";
            }
            if ( ! empty( $page_context['page_goal'] ) ) {
                $goals = [
                    'inform'  => 'Inform visitors about a service or product',
                    'convert' => 'Convert visitors into leads or customers',
                    'educate' => 'Educate visitors on a topic',
                    'sell'    => 'Sell a specific product or service',
                    'support' => 'Provide support or answer questions',
                ];
                $goal_label = $goals[ $page_context['page_goal'] ] ?? $page_context['page_goal'];
                $prompt .= "- Page goal: {$goal_label}\n";
            }
            if ( ! empty( $page_context['target_audience'] ) ) {
                $prompt .= "- Target audience for this page: {$page_context['target_audience']}\n";
            }
        }
        $prompt .= "\n";

        if ( ! empty( $business_profile ) ) {
            $prompt .= "## Business Profile\n";
            if ( ! empty( $business_profile['tagline'] ) ) {
                $prompt .= "- Tagline: {$business_profile['tagline']}\n";
            }
            if ( ! empty( $business_profile['one_sentence'] ) ) {
                $prompt .= "- About: {$business_profile['one_sentence']}\n";
            }
            if ( ! empty( $business_profile['tone'] ) ) {
                $prompt .= "- Brand tone: {$business_profile['tone']}\n";
            }
            if ( ! empty( $business_profile['customer_description'] ) ) {
                $prompt .= "- Target customer: {$business_profile['customer_description']}\n";
            }
            if ( ! empty( $business_profile['problem_solved'] ) ) {
                $prompt .= "- Problem solved: {$business_profile['problem_solved']}\n";
            }
            if ( ! empty( $business_profile['differentiators'] ) ) {
                $prompt .= "- Differentiators: {$business_profile['differentiators']}\n";
            }
            if ( ! empty( $business_profile['geography'] ) ) {
                $prompt .= "- Service area: {$business_profile['geography']}\n";
            }
            if ( ! empty( $business_profile['always_include'] ) ) {
                $prompt .= "- Must include: {$business_profile['always_include']}\n";
            }
            if ( ! empty( $business_profile['topics_to_avoid'] ) ) {
                $prompt .= "- Avoid: {$business_profile['topics_to_avoid']}\n";
            }
            $prompt .= "\n";

            // B-SMART dimensions.
            $bsmart = [ 'brand', 'size', 'material', 'application', 'requirements', 'type' ];
            $bsmart_parts = [];
            foreach ( $bsmart as $dim ) {
                if ( ! empty( $business_profile[ $dim ] ) && 'N/A' !== $business_profile[ $dim ] ) {
                    $bsmart_parts[] = ucfirst( $dim ) . ': ' . $business_profile[ $dim ];
                }
            }
            if ( ! empty( $bsmart_parts ) ) {
                $prompt .= "## B-SMART Context\n";
                $prompt .= implode( "\n", $bsmart_parts ) . "\n\n";
            }
        }

        $prompt .= "## Required Content Fields\n";
        $prompt .= "Generate content for the following fields. Return a JSON object with these exact keys.\n\n";
        $prompt .= $fields_description . "\n";

        // Reference URL.
        if ( ! empty( $page_context['reference_url'] ) ) {
            $prompt .= "## Reference URL\n";
            $prompt .= "Use this URL as a reference for content style or information: {$page_context['reference_url']}\n\n";
        }

        // Uploaded file content.
        if ( ! empty( $file_content ) ) {
            $prompt .= "## Reference File Content\n";
            $prompt .= "The following content was provided as a reference document. Use it to inform the generated content:\n```\n{$file_content}\n```\n\n";
        }

        // AI notes / special instructions.
        if ( ! empty( $page_context['ai_notes'] ) ) {
            $prompt .= "## Special Instructions\n";
            $prompt .= "{$page_context['ai_notes']}\n\n";
        }

        $prompt .= "## Rules\n";
        $prompt .= "1. All text must be unique — do not duplicate content across fields.\n";
        $prompt .= "2. Headings should be short (5-12 words), impactful, and not repeat the page name verbatim.\n";
        $prompt .= "3. Body text should be informative and written for the target customer.\n";
        $prompt .= "4. For list/repeater fields, generate the number of items specified.\n";
        $prompt .= "5. Do not include placeholder text like 'Lorem ipsum'.\n";
        $prompt .= "6. Match the brand tone specified in the business profile.\n";
        $prompt .= "7. Return ONLY valid JSON — no explanation text before or after.\n";
        if ( ! empty( $page_context['target_keywords'] ) ) {
            $prompt .= "8. Naturally incorporate the target keywords into the content.\n";
        }

        return $prompt;
    }

    /**
     * Build the content extraction prompt for scraped content.
     */
    private function build_extraction_prompt( string $raw_content, array $headings, string $page_name, string $template_slug, array $mapping ): string {
        $fields_description = $this->describe_expected_fields( $mapping );

        // Truncate raw content to avoid token limits.
        $max_content_chars = 8000;
        if ( mb_strlen( $raw_content ) > $max_content_chars ) {
            $raw_content = mb_substr( $raw_content, 0, $max_content_chars ) . "\n[Content truncated...]";
        }

        $headings_text = '';
        foreach ( $headings as $h ) {
            $headings_text .= "  {$h['level']}: {$h['text']}\n";
        }

        $prompt = "You are a content extraction assistant. Extract and map website content into structured fields.\n\n";
        $prompt .= "## Source Page\n";
        $prompt .= "- Page name: {$page_name}\n";
        $prompt .= "- Template type: {$template_slug}\n\n";

        if ( ! empty( $headings_text ) ) {
            $prompt .= "## Headings Found\n{$headings_text}\n";
        }

        $prompt .= "## Raw Page Content\n```\n{$raw_content}\n```\n\n";

        $prompt .= "## Required Output Fields\n";
        $prompt .= "Map the scraped content into the following fields. Return a JSON object with these exact keys.\n\n";
        $prompt .= $fields_description . "\n";

        $prompt .= "## Rules\n";
        $prompt .= "1. Use the actual content from the scraped page — do not invent content.\n";
        $prompt .= "2. If a field cannot be filled from the scraped content, use a brief relevant placeholder based on the page topic.\n";
        $prompt .= "3. For list/repeater fields, extract as many items as available from the source.\n";
        $prompt .= "4. Clean up any HTML tags, navigation text, or UI artifacts from the content.\n";
        $prompt .= "5. Return ONLY valid JSON — no explanation text before or after.\n";

        return $prompt;
    }

    /**
     * Describe the expected fields based on the mapping.
     */
    private function describe_expected_fields( array $mapping ): string {
        $fields = [];

        foreach ( $mapping as $entry ) {
            $name = $entry['content_field_name'];
            $type = $entry['content_field_type'];
            $widget = $entry['widget_type'];
            $widget_field = $entry['field'];

            if ( isset( $fields[ $name ] ) ) {
                continue; // Skip duplicates (e.g., group items mapped to same field).
            }

            if ( 'repeater' === $type ) {
                if ( in_array( $widget, [ 'accordion', 'toggle' ], true ) ) {
                    $fields[ $name ] = "- `{$name}`: Array of objects with `title` and `content` keys. Generate 4-6 items.";
                } elseif ( 'tabs' === $widget ) {
                    $fields[ $name ] = "- `{$name}`: Array of objects with `title` and `content` keys. Generate 3-5 items.";
                } elseif ( 'icon-list' === $widget ) {
                    $fields[ $name ] = "- `{$name}`: Array of objects with `text` key. Generate 4-8 items.";
                } else {
                    $fields[ $name ] = "- `{$name}`: Array of items. Generate 3-6 items.";
                }
            } elseif ( 'group_item' === $type ) {
                // These are individual fields within a widget group.
                if ( strpos( $widget_field, 'title' ) !== false ) {
                    $fields[ $name ] = "- `{$name}`: Short title (5-10 words).";
                } else {
                    $fields[ $name ] = "- `{$name}`: Description paragraph (1-3 sentences).";
                }
            } else {
                // Text fields.
                if ( strpos( $name, 'heading' ) !== false || strpos( $name, 'title' ) !== false ) {
                    $fields[ $name ] = "- `{$name}`: Heading text (5-12 words).";
                } elseif ( strpos( $name, 'description' ) !== false || strpos( $name, 'content' ) !== false || strpos( $name, 'about' ) !== false ) {
                    $fields[ $name ] = "- `{$name}`: Body text paragraph (2-4 sentences).";
                } elseif ( strpos( $name, 'call_to_action' ) !== false || strpos( $name, 'button' ) !== false || strpos( $name, 'cta' ) !== false ) {
                    $fields[ $name ] = "- `{$name}`: Button text (2-5 words).";
                } else {
                    $fields[ $name ] = "- `{$name}`: Text content.";
                }
            }
        }

        return implode( "\n", $fields );
    }

    /**
     * Extract JSON from Claude's response text.
     */
    private function extract_json( string $text ): ?array {
        // Try to parse the whole text as JSON first.
        $decoded = json_decode( $text, true );
        if ( is_array( $decoded ) ) {
            return $decoded;
        }

        // Look for JSON inside code blocks.
        if ( preg_match( '/```(?:json)?\s*\n?(.*?)\n?```/s', $text, $matches ) ) {
            $decoded = json_decode( $matches[1], true );
            if ( is_array( $decoded ) ) {
                return $decoded;
            }
        }

        // Look for the first { ... } block.
        if ( preg_match( '/\{.*\}/s', $text, $matches ) ) {
            $decoded = json_decode( $matches[0], true );
            if ( is_array( $decoded ) ) {
                return $decoded;
            }
        }

        return null;
    }
}
