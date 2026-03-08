<?php
/**
 * ContentHub WP Uninstall
 *
 * Cleans up all plugin data when uninstalled.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Remove plugin options.
delete_option( 'contenthub_api_key' );
delete_option( 'contenthub_business_profile' );
delete_option( 'contenthub_template_types' );
delete_option( 'contenthub_settings' );

// Remove all template mapping options.
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'contenthub_template_mapping_%'" );

// Remove all postmeta entries.
$meta_keys = [
    '_contenthub_template_type',
    '_contenthub_content_data',
    '_contenthub_content_source',
    '_contenthub_content_status',
    '_contenthub_backup_elementor_data',
    '_contenthub_deployed_at',
];

foreach ( $meta_keys as $key ) {
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", $key ) );
}
