<?php
/**
 * Public client content portal.
 * Rendered standalone — no WP admin chrome.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$token        = sanitize_text_field( wp_unslash( $_GET['contenthub_share'] ) );
$rest_url     = esc_url( rest_url( 'contenthub-wp/v1/' ) );
$site_name    = html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES, 'UTF-8' );
$site_icon    = get_site_icon_url( 64 );
$ai_available = ContentHub_Settings::instance()->has_api_key();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Content Portal — <?php echo esc_html( $site_name ); ?></title>
    <?php if ( $site_icon ) : ?>
        <link rel="icon" href="<?php echo esc_url( $site_icon ); ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo esc_url( CONTENTHUB_WP_URL . 'portal-ui/build/index.css' ); ?>">
</head>
<body>
    <div id="contenthub-portal"></div>
    <script>
        window.__CONTENTHUB_PORTAL__ = {
            restUrl: <?php echo wp_json_encode( $rest_url ); ?>,
            token: <?php echo wp_json_encode( $token ); ?>,
            siteName: <?php echo wp_json_encode( $site_name ); ?>,
            siteIconUrl: <?php echo wp_json_encode( $site_icon ); ?>,
            aiAvailable: <?php echo wp_json_encode( $ai_available ); ?>,
        };
    </script>
    <script src="<?php echo esc_url( CONTENTHUB_WP_URL . 'portal-ui/build/index.js' ); ?>"></script>
</body>
</html>
