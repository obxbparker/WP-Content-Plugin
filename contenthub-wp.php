<?php
/**
 * Plugin Name: OBX ContentHub
 * Description: AI-powered content management and Elementor deployment plugin. Scrape, write, or generate content and deploy it to Elementor-built page templates.
 * Version:     1.1.2-beta
 * Author:      OuterBox
 * Author URI:  https://outerboxdesign.com
 * License:     GPL-2.0-or-later
 * Text Domain: contenthub-wp
 * Requires PHP: 7.4
 * Requires at least: 5.8
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CONTENTHUB_WP_VERSION', '1.1.2-beta' );
define( 'CONTENTHUB_WP_PATH', plugin_dir_path( __FILE__ ) );
define( 'CONTENTHUB_WP_URL', plugin_dir_url( __FILE__ ) );
define( 'CONTENTHUB_WP_MIN_ELEMENTOR_VERSION', '3.0.0' );

/**
 * Check if Elementor is active and meets minimum version.
 */
function contenthub_wp_check_elementor() {
    if ( ! did_action( 'elementor/loaded' ) ) {
        add_action( 'admin_notices', 'contenthub_wp_missing_elementor_notice' );
        return false;
    }

    if ( defined( 'ELEMENTOR_VERSION' ) && version_compare( ELEMENTOR_VERSION, CONTENTHUB_WP_MIN_ELEMENTOR_VERSION, '<' ) ) {
        add_action( 'admin_notices', 'contenthub_wp_outdated_elementor_notice' );
        return false;
    }

    return true;
}

function contenthub_wp_missing_elementor_notice() {
    ?>
    <div class="notice notice-error">
        <p><strong>OBX ContentHub</strong> requires <a href="https://wordpress.org/plugins/elementor/" target="_blank">Elementor</a> to be installed and activated.</p>
    </div>
    <?php
}

function contenthub_wp_outdated_elementor_notice() {
    ?>
    <div class="notice notice-error">
        <p><strong>OBX ContentHub</strong> requires Elementor version <?php echo esc_html( CONTENTHUB_WP_MIN_ELEMENTOR_VERSION ); ?> or higher.</p>
    </div>
    <?php
}

/**
 * Activation hook.
 */
function contenthub_wp_activate() {
    // Initialize default options if they don't exist.
    if ( false === get_option( 'contenthub_template_types' ) ) {
        update_option( 'contenthub_template_types', wp_json_encode( [] ) );
    }
    if ( false === get_option( 'contenthub_settings' ) ) {
        update_option( 'contenthub_settings', wp_json_encode( [
            'backup_before_deploy' => true,
            'clear_cache_after_deploy' => true,
        ] ) );
    }
    if ( false === get_option( 'contenthub_business_profile' ) ) {
        update_option( 'contenthub_business_profile', wp_json_encode( [] ) );
    }
}
register_activation_hook( __FILE__, 'contenthub_wp_activate' );

/**
 * Load plugin after all plugins are loaded.
 */
function contenthub_wp_init() {
    if ( ! contenthub_wp_check_elementor() ) {
        return;
    }

    require_once CONTENTHUB_WP_PATH . 'includes/class-contenthub-wp.php';

    ContentHub_WP::instance();
}
add_action( 'plugins_loaded', 'contenthub_wp_init' );
