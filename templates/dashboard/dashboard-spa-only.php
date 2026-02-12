<?php
/**
 * Template loader: outputs only the SPA shell (no theme).
 * Used when a page with [puzzling_dashboard] is viewed and SPA build exists.
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Pass current page path as basename for React Router (shortcode page may not be at /dashboard)
global $post;
if ( $post instanceof WP_Post ) {
	$page_url = get_permalink( $post );
	$page_path = $page_url ? wp_parse_url( $page_url, PHP_URL_PATH ) : '';
	if ( is_string( $page_path ) && $page_path !== '' ) {
		define( 'PUZZLINGCRM_SPA_BASEPATH', rtrim( $page_path, '/' ) );
	}
}

include PUZZLINGCRM_PLUGIN_DIR . 'templates/dashboard/dashboard-spa-shell.php';
exit;
