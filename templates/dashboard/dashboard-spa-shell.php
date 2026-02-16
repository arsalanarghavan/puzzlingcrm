<?php
/**
 * SPA Shell for React Dashboard
 * Minimal HTML + root div + bootstrap data. All /dashboard* routes get this;
 * React Router handles client-side routing.
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure data providers are loaded (they may not be if component registry was never used).
if ( ! class_exists( 'PuzzlingCRM_Sidebar_Menu_Builder' ) ) {
	require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/components/sidebar/class-sidebar-menu-builder.php';
}
if ( ! class_exists( 'PuzzlingCRM_Sidebar_Data_Provider' ) ) {
	require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/components/sidebar/class-sidebar-data-provider.php';
}
if ( ! class_exists( 'PuzzlingCRM_Header_Data_Provider' ) ) {
	require_once PUZZLINGCRM_PLUGIN_DIR . 'includes/components/header/class-header-data-provider.php';
}

$user_obj     = wp_get_current_user();
$user_role    = PuzzlingCRM_Dashboard_Router::get_user_dashboard_role( $user_obj );
$routes       = PuzzlingCRM_Dashboard_Router::get_routes();
$menu_items_raw = class_exists( 'PuzzlingCRM_Sidebar_Menu_Builder' ) ? PuzzlingCRM_Sidebar_Menu_Builder::build_menu( $user_role ) : array();
// Normalize menu items: ensure each link item has 'id' or 'url' for React key and navigation.
$menu_items = array();
foreach ( $menu_items_raw as $idx => $item ) {
	$normalized = is_array( $item ) ? $item : array();
	if ( ! empty( $normalized['type'] ) && $normalized['type'] === 'category' ) {
		$menu_items[] = array( 'type' => 'category', 'title' => isset( $normalized['title'] ) ? $normalized['title'] : '' );
	} else {
		if ( empty( $normalized['id'] ) && ! empty( $normalized['url'] ) ) {
			$normalized['id'] = 'item-' . $idx;
		}
		$menu_items[] = $normalized;
	}
}
$user_data    = class_exists( 'PuzzlingCRM_Header_Data_Provider' ) ? PuzzlingCRM_Header_Data_Provider::get_user_data() : array( 'id' => 0, 'name' => '', 'email' => '', 'avatar' => '', 'role' => '' );
$profile_menu = class_exists( 'PuzzlingCRM_Header_Data_Provider' ) ? PuzzlingCRM_Header_Data_Provider::get_profile_menu_items() : array();
$logo_url     = class_exists( 'PuzzlingCRM_Sidebar_Data_Provider' ) ? PuzzlingCRM_Sidebar_Data_Provider::get_logo_url() : '';

$cookie_lang = isset( $_COOKIE['pzl_language'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['pzl_language'] ) ) : '';
$locale      = get_locale();
if ( $cookie_lang === 'en' ) {
	$locale = 'en_US';
} elseif ( $cookie_lang === 'fa' ) {
	$locale = 'fa_IR';
}
$is_rtl    = ( $locale === 'fa_IR' );
$direction = $is_rtl ? 'rtl' : 'ltr';
$lang      = substr( $locale, 0, 2 );

$site_name = get_bloginfo( 'name' );
if ( class_exists( 'PuzzlingCRM_White_Label' ) ) {
	$site_name = PuzzlingCRM_White_Label::get_company_name();
}

$routes_for_js = array();
foreach ( $routes as $slug => $r ) {
	$routes_for_js[ $slug ] = array(
		'id'     => $slug,
		'title'  => $r['title'],
		'icon'   => $r['icon'],
		'roles'  => $r['roles'],
		'path'   => $slug ? '/dashboard/' . $slug : '/dashboard',
	);
}

$profile_menu_for_js = array();
foreach ( $profile_menu as $item ) {
	$profile_menu_for_js[] = array(
		'title' => isset( $item['title'] ) ? $item['title'] : '',
		'url'   => isset( $item['url'] ) ? $item['url'] : '',
		'icon'  => isset( $item['icon'] ) ? $item['icon'] : '',
	);
}

$bootstrap = array(
	'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
	'nonce'       => wp_create_nonce( 'puzzlingcrm-ajax-nonce' ),
	'user'        => ( isset( $user_data['id'] ) && (int) $user_data['id'] > 0 ) ? $user_data : null,
	'routes'      => $routes_for_js,
	'menuItems'   => $menu_items,
	'profileMenu' => $profile_menu_for_js,
	'i18n'        => array(
		'success_title' => __( 'موفق', 'puzzlingcrm' ),
		'error_title'   => __( 'خطا', 'puzzlingcrm' ),
		'ok_button'     => __( 'باشه', 'puzzlingcrm' ),
		'server_error'  => __( 'خطای سرور', 'puzzlingcrm' ),
	),
	'siteUrl'          => home_url( '/' ),
	'dashboardBasePath' => ( function() {
		if ( defined( 'PUZZLINGCRM_SPA_BASEPATH' ) && PUZZLINGCRM_SPA_BASEPATH !== '' ) {
			return PUZZLINGCRM_SPA_BASEPATH;
		}
		$path = parse_url( home_url( '/dashboard' ), PHP_URL_PATH );
		return ( is_string( $path ) && $path !== '' ) ? rtrim( $path, '/' ) : '/dashboard';
	} )(),
	'embedBaseUrl'   => ( function() {
		$home = home_url( '/' );
		return rtrim( $home, '/' );
	} )(),
	'loginUrl'         => home_url( '/login' ),
	'logoutUrl'        => wp_logout_url( home_url( '/dashboard' ) ),
	'isRtl'            => $is_rtl,
	'locale'    => $lang,
	'logoUrl'   => $logo_url,
);

$build_dir = PUZZLINGCRM_PLUGIN_DIR . 'assets/dashboard-build/';
$build_url = PUZZLINGCRM_PLUGIN_URL . 'assets/dashboard-build/';
$version   = defined( 'PUZZLINGCRM_VERSION' ) ? PUZZLINGCRM_VERSION : '1.0.0';
$main_js_index = $build_dir . 'dashboard-index.js';
$main_js_main  = $build_dir . 'dashboard-main.js';
if ( file_exists( $main_js_index ) ) {
	$spa_js  = 'dashboard-index.js';
	$spa_css = 'dashboard-index.css';
} elseif ( file_exists( $main_js_main ) ) {
	$spa_js  = 'dashboard-main.js';
	$spa_css = 'dashboard-main.css';
} else {
	// SPA not built yet: fall back to legacy dashboard wrapper
	$wrapper_file = PUZZLINGCRM_PLUGIN_DIR . 'templates/dashboard/dashboard-wrapper.php';
	if ( file_exists( $wrapper_file ) ) {
		include $wrapper_file;
	}
	return;
}

// If expected CSS file is missing (e.g. Vite emitted dashboard-tabs.css), use any dashboard-*.css
if ( ! file_exists( $build_dir . $spa_css ) ) {
	$dashboard_css_files = glob( $build_dir . 'dashboard-*.css' );
	if ( ! empty( $dashboard_css_files ) ) {
		$spa_css = basename( $dashboard_css_files[0] );
	}
}

// Strong cache busting: use file modification time so any rebuild triggers fresh load
$js_file  = $build_dir . $spa_js;
$css_file = $build_dir . $spa_css;
$js_mtime = file_exists( $js_file ) ? filemtime( $js_file ) : $version;
$css_mtime = file_exists( $css_file ) ? filemtime( $css_file ) : $version;
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( $lang ); ?>" dir="<?php echo esc_attr( $direction ); ?>">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title><?php echo esc_html( $site_name ); ?> - <?php esc_html_e( 'داشبورد', 'puzzlingcrm' ); ?></title>
	<?php wp_head(); ?>
	<link rel="stylesheet" href="<?php echo esc_url( PUZZLINGCRM_PLUGIN_URL . 'assets/css/fonts.css' ); ?>?v=<?php echo esc_attr( $version ); ?>" />
	<link rel="stylesheet" href="<?php echo esc_url( $build_url . $spa_css ); ?>?v=<?php echo esc_attr( $css_mtime ); ?>" />
	<style type="text/css">
		body.pzl-dashboard-spa {
			min-height: 100vh;
			margin: 0;
			background-color: hsl(var(--background, 0 0% 100%));
			color: hsl(var(--foreground, 222.2 84% 4.9%));
		}
		body.pzl-dashboard-spa #root {
			min-height: 100vh;
			min-width: 100%;
			display: flex;
			flex-direction: column;
			flex: 1;
			background-color: inherit;
			color: inherit;
		}
	</style>
</head>
<body class="pzl-dashboard-spa" data-dashboard="spa">
	<div id="root"></div>
	<script>
		window.puzzlingcrm = <?php echo wp_json_encode( $bootstrap ); ?>;
	</script>
	<script type="module" src="<?php echo esc_url( $build_url . $spa_js ); ?>?v=<?php echo esc_attr( $js_mtime ); ?>"></script>
	<?php wp_footer(); ?>
</body>
</html>
