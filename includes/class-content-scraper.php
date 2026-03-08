<?php
/**
 * Scrapes content from URLs using PHP (wp_remote_get + DOMDocument).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ContentHub_Content_Scraper {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Scrape a URL and return extracted content.
     *
     * @param string $url The URL to scrape.
     * @return array|WP_Error {content, headings, title, meta_description}
     */
    public function scrape_url( string $url ) {
        $url = esc_url_raw( $url );

        // Validate URL.
        if ( ! wp_http_validate_url( $url ) ) {
            return new WP_Error( 'invalid_url', 'The provided URL is not valid.' );
        }

        $response = wp_remote_get( $url, [
            'timeout'    => 30,
            'user-agent' => 'ContentHub-WP/1.0 (WordPress Plugin)',
            'sslverify'  => false,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status = wp_remote_retrieve_response_code( $response );
        if ( $status >= 400 ) {
            return new WP_Error( 'http_error', "URL returned HTTP status {$status}." );
        }

        $html = wp_remote_retrieve_body( $response );
        if ( empty( $html ) ) {
            return new WP_Error( 'empty_response', 'The URL returned an empty response.' );
        }

        return $this->parse_html( $html );
    }

    /**
     * Parse HTML and extract structured content.
     */
    private function parse_html( string $html ): array {
        // Suppress DOMDocument warnings for malformed HTML.
        libxml_use_internal_errors( true );

        $doc = new DOMDocument();
        $doc->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
        libxml_clear_errors();

        // Extract title.
        $title = '';
        $title_nodes = $doc->getElementsByTagName( 'title' );
        if ( $title_nodes->length > 0 ) {
            $title = trim( $title_nodes->item( 0 )->textContent );
        }

        // Extract meta description.
        $meta_description = '';
        $metas = $doc->getElementsByTagName( 'meta' );
        foreach ( $metas as $meta ) {
            if ( strtolower( $meta->getAttribute( 'name' ) ) === 'description' ) {
                $meta_description = $meta->getAttribute( 'content' );
                break;
            }
        }

        // Remove script and style elements.
        $this->remove_elements( $doc, 'script' );
        $this->remove_elements( $doc, 'style' );
        $this->remove_elements( $doc, 'nav' );
        $this->remove_elements( $doc, 'footer' );
        $this->remove_elements( $doc, 'header' );
        $this->remove_elements( $doc, 'noscript' );

        // Extract headings.
        $headings = $this->extract_headings( $doc );

        // Try to find main content area.
        $content = $this->extract_main_content( $doc );

        return [
            'content'          => $content,
            'headings'         => $headings,
            'title'            => $title,
            'meta_description' => $meta_description,
        ];
    }

    /**
     * Extract main content from the page.
     */
    private function extract_main_content( DOMDocument $doc ): string {
        // Try semantic elements first.
        $candidates = [ 'main', 'article' ];
        foreach ( $candidates as $tag ) {
            $elements = $doc->getElementsByTagName( $tag );
            if ( $elements->length > 0 ) {
                return $this->get_text_content( $elements->item( 0 ) );
            }
        }

        // Try common content IDs/classes.
        $xpath = new DOMXPath( $doc );
        $selectors = [
            '//*[@id="content"]',
            '//*[@id="main-content"]',
            '//*[@id="primary"]',
            '//*[contains(@class, "entry-content")]',
            '//*[contains(@class, "page-content")]',
            '//*[contains(@class, "post-content")]',
            '//*[contains(@class, "elementor")]',
        ];

        foreach ( $selectors as $selector ) {
            $nodes = $xpath->query( $selector );
            if ( $nodes && $nodes->length > 0 ) {
                return $this->get_text_content( $nodes->item( 0 ) );
            }
        }

        // Fallback: get body text.
        $body = $doc->getElementsByTagName( 'body' );
        if ( $body->length > 0 ) {
            return $this->get_text_content( $body->item( 0 ) );
        }

        return '';
    }

    /**
     * Extract all headings from the document.
     */
    private function extract_headings( DOMDocument $doc ): array {
        $headings = [];
        $xpath = new DOMXPath( $doc );
        $nodes = $xpath->query( '//h1|//h2|//h3|//h4|//h5|//h6' );

        if ( $nodes ) {
            foreach ( $nodes as $node ) {
                $text = trim( $node->textContent );
                if ( ! empty( $text ) ) {
                    $headings[] = [
                        'level' => strtolower( $node->nodeName ),
                        'text'  => $text,
                    ];
                }
            }
        }

        return $headings;
    }

    /**
     * Get clean text content from a DOM node.
     */
    private function get_text_content( DOMNode $node ): string {
        $text = $node->textContent;
        // Normalize whitespace.
        $text = preg_replace( '/\s+/', ' ', $text );
        $text = preg_replace( '/\n\s*\n/', "\n\n", $text );
        return trim( $text );
    }

    /**
     * Remove all elements of a given tag name.
     */
    private function remove_elements( DOMDocument $doc, string $tag_name ): void {
        $elements = $doc->getElementsByTagName( $tag_name );
        $to_remove = [];
        foreach ( $elements as $el ) {
            $to_remove[] = $el;
        }
        foreach ( $to_remove as $el ) {
            if ( $el->parentNode ) {
                $el->parentNode->removeChild( $el );
            }
        }
    }

    /**
     * Scrape and extract content into structured fields using Claude AI.
     *
     * @param string $url            URL to scrape.
     * @param string $page_name      Name of the page.
     * @param string $template_slug  Template type slug.
     * @param array  $mapping        Template field mapping.
     * @return array|WP_Error Structured content data.
     */
    public function scrape_and_extract( string $url, string $page_name, string $template_slug, array $mapping ) {
        $scraped = $this->scrape_url( $url );
        if ( is_wp_error( $scraped ) ) {
            return $scraped;
        }

        $generator = ContentHub_Content_Generator::instance();
        return $generator->extract_from_scraped(
            $scraped['content'],
            $scraped['headings'],
            $page_name,
            $template_slug,
            $mapping
        );
    }
}
