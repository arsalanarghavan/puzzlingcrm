<?php
/**
 * Embed Shell for SPA iframe - Partial content only (no header/sidebar).
 * Loads necessary assets and the requested partial.
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$assets_url   = PUZZLINGCRM_PLUGIN_URL . 'assets/';
$embed_slug   = get_query_var( 'embed_page', '' );
$cookie_lang  = isset( $_COOKIE['pzl_language'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['pzl_language'] ) ) : '';
$locale       = get_locale();
if ( $cookie_lang === 'en' ) {
	$locale = 'en_US';
} elseif ( $cookie_lang === 'fa' ) {
	$locale = 'fa_IR';
}
$is_rtl       = ( $locale === 'fa_IR' );
$direction    = $is_rtl ? 'rtl' : 'ltr';
$lang         = substr( $locale, 0, 2 );
$bootstrap_css = $is_rtl ? 'bootstrap.rtl.min.css' : 'bootstrap.min.css';
$routes       = PuzzlingCRM_Dashboard_Router::get_routes();
$route_title  = isset( $routes[ $embed_slug ] ) ? $routes[ $embed_slug ]['title'] : '';
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( $lang ); ?>" dir="<?php echo esc_attr( $direction ); ?>">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( $route_title ); ?> - PuzzlingCRM</title>
	<link id="style" href="<?php echo esc_url( $assets_url . 'libs/bootstrap/css/' . $bootstrap_css ); ?>" rel="stylesheet">
	<link href="<?php echo esc_url( $assets_url . 'css/fonts.css' ); ?>" rel="stylesheet">
	<link href="<?php echo esc_url( $assets_url . 'css/styles.css' ); ?>" rel="stylesheet">
	<link href="<?php echo esc_url( $assets_url . 'css/icons.css' ); ?>" rel="stylesheet">
	<link href="<?php echo esc_url( $assets_url . 'libs/simplebar/simplebar.min.css' ); ?>" rel="stylesheet">
	<link href="<?php echo esc_url( $assets_url . 'css/puzzlingcrm-xintra-bridge.css' ); ?>" rel="stylesheet">
	<link href="<?php echo esc_url( $assets_url . 'css/puzzlingcrm-custom.css' ); ?>?v=<?php echo esc_attr( PUZZLINGCRM_VERSION ); ?>" rel="stylesheet">
	<link href="<?php echo esc_url( $assets_url . 'css/rtl-complete-fix.css' ); ?>?v=<?php echo esc_attr( PUZZLINGCRM_VERSION ); ?>" rel="stylesheet">
	<link href="<?php echo esc_url( PUZZLINGCRM_PLUGIN_URL . 'assets/css/all-pages-complete.css' ); ?>?v=<?php echo esc_attr( PUZZLINGCRM_VERSION ); ?>" rel="stylesheet">
	<?php if ( $embed_slug === 'reports' ) : ?>
	<link href="<?php echo esc_url( PUZZLINGCRM_PLUGIN_URL . 'assets/css/reports-styles.css' ); ?>?v=<?php echo esc_attr( PUZZLINGCRM_VERSION ); ?>" rel="stylesheet">
	<?php endif; ?>
	<?php if ( $embed_slug === 'licenses' ) : ?>
	<link href="<?php echo esc_url( PUZZLINGCRM_PLUGIN_URL . 'assets/css/licenses-styles.css' ); ?>?v=<?php echo esc_attr( PUZZLINGCRM_VERSION ); ?>" rel="stylesheet">
	<?php endif; ?>
	<?php wp_head(); ?>
	<script>
	window.puzzlingcrm_ajax_obj = window.puzzlingcrm_ajax_obj || {};
	window.puzzlingcrm_ajax_obj.ajax_url = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
	window.puzzlingcrm_ajax_obj.nonce = '<?php echo esc_js( wp_create_nonce( 'puzzlingcrm-ajax-nonce' ) ); ?>';
	window.puzzlingcrm_ajax_obj.lang = { success_title: 'موفق', error_title: 'خطا', ok_button: 'باشه', server_error: 'خطای سرور' };
	</script>
</head>
<body class="pzl-embed-body app" dir="<?php echo esc_attr( $direction ); ?>">
	<div class="container-fluid p-4">
		<?php
		$partial_path = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/' . ( $embed_partial ?? '' ) . '.php';
		if ( ! empty( $embed_partial ) && file_exists( $partial_path ) ) {
			include $partial_path;
		} else {
			echo '<p class="alert alert-danger">' . esc_html__( 'صفحه یافت نشد.', 'puzzlingcrm' ) . '</p>';
		}
		?>
	</div>
	<?php
	wp_enqueue_script( 'jquery' );
	wp_print_scripts( 'jquery' );
	?>
	<script>
	if (typeof jQuery === 'undefined') {
		document.write('<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"><\/script>');
	}
	</script>
	<script src="<?php echo esc_url( $assets_url . 'libs/bootstrap/js/bootstrap.bundle.min.js' ); ?>"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script src="<?php echo esc_url( PUZZLINGCRM_PLUGIN_URL . 'assets/js/puzzlingcrm-scripts.js' ); ?>?v=<?php echo esc_attr( PUZZLINGCRM_VERSION ); ?>"></script>
	<?php if ( $embed_slug === 'leads' ) : ?>
	<link rel="stylesheet" href="<?php echo esc_url( PUZZLINGCRM_PLUGIN_URL . 'assets/libs/dragula/dragula.min.css' ); ?>">
	<script src="<?php echo esc_url( PUZZLINGCRM_PLUGIN_URL . 'assets/libs/dragula/dragula.min.js' ); ?>"></script>
	<script src="<?php echo esc_url( PUZZLINGCRM_PLUGIN_URL . 'assets/js/leads-page-init.js' ); ?>?v=<?php echo esc_attr( PUZZLINGCRM_VERSION ); ?>"></script>
	<?php endif; ?>
	<?php wp_footer(); ?>
</body>
</html>
