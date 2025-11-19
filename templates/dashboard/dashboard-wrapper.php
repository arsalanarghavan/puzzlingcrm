<?php
/**
 * Dashboard Wrapper Template
 * 
 * Main wrapper for all dashboard pages using components
 * EXACT COPY FROM MANELI - All JavaScript functions copied directly
 *
 * @package PuzzlingCRM
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get component registry
$registry = PuzzlingCRM_Component_Registry::instance();
$header   = $registry->get( 'header' );
$sidebar  = $registry->get( 'sidebar' );
$footer   = $registry->get( 'footer' );

// Get current route and user
$current_page = get_query_var( 'dashboard_page', '' );
$current_user = wp_get_current_user();
$user_role    = '';

if ( $current_user ) {
	$roles = (array) $current_user->roles;
	if ( in_array( 'administrator', $roles, true ) || in_array( 'system_manager', $roles, true ) ) {
		$user_role = 'system_manager';
	} elseif ( in_array( 'finance_manager', $roles, true ) ) {
		$user_role = 'finance_manager';
	} elseif ( in_array( 'team_member', $roles, true ) ) {
		$user_role = 'team_member';
	} elseif ( in_array( 'customer', $roles, true ) ) {
		$user_role = 'customer';
	}
}

// Get page title
$page_title = __( 'Dashboard', 'puzzlingcrm' );
if ( ! empty( $current_page ) ) {
	$routes = PuzzlingCRM_Dashboard_Router::get_routes();
	if ( isset( $routes[ $current_page ] ) ) {
		$page_title = $routes[ $current_page ]['title'];
	}
}

// Determine language and direction (check cookie first)
$cookie_lang = isset( $_COOKIE['pzl_language'] ) ? sanitize_text_field( $_COOKIE['pzl_language'] ) : '';
$locale      = get_locale();

// Override locale if cookie is set
if ( $cookie_lang === 'en' ) {
	$locale = 'en_US';
} elseif ( $cookie_lang === 'fa' ) {
	$locale = 'fa_IR';
}

$is_rtl    = ( $locale === 'fa_IR' );
$direction = $is_rtl ? 'rtl' : 'ltr';
$lang      = substr( $locale, 0, 2 );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> dir="<?php echo esc_attr( $direction ); ?>" data-nav-layout="vertical" data-header-styles="light" data-menu-styles="dark" data-toggled="open">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="description" content="<?php bloginfo( 'description' ); ?>">
	<meta name="author" content="<?php bloginfo( 'name' ); ?>">
	
	<title><?php echo esc_html( $page_title ); ?> - <?php bloginfo( 'name' ); ?></title>
	
	<!-- Favicon -->
	<link rel="icon" href="<?php echo esc_url( PUZZLINGCRM_PLUGIN_URL . 'assets/images/brand-logos/favicon.ico' ); ?>" type="image/x-icon">
	
	<!-- Global Search Styles (from maneli) -->
	<style>
	.global-search-dropdown {
		position: absolute;
		top: 100%;
		left: 0;
		right: 0;
		background: #fff;
		border: 1px solid #dee2e6;
		border-radius: 8px;
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
		max-height: 500px;
		overflow-y: auto;
		z-index: 1050;
		margin-top: 5px;
	}
	
	[data-theme-mode=dark] .global-search-dropdown {
		background: rgb(25, 25, 28);
		border-color: rgba(255, 255, 255, 0.1);
	}
	
	.search-section {
		border-bottom: 1px solid #e9ecef;
		padding: 8px 0;
	}
	
	.search-section:last-child {
		border-bottom: none;
	}
	
	.search-section-title {
		font-weight: 600;
		font-size: 13px;
		color: #6c757d;
		padding: 8px 15px 4px;
		text-transform: uppercase;
		letter-spacing: 0.5px;
	}
	
	[data-theme-mode=dark] .search-section-title {
		color: rgba(255, 255, 255, 0.7);
	}
	
	.search-result-item {
		display: block;
		padding: 10px 15px;
		text-decoration: none;
		color: inherit;
		border-bottom: 1px solid #f0f0f0;
		transition: background-color 0.2s;
	}
	
	.search-result-item:hover {
		background-color: #f8f9fa;
		text-decoration: none;
	}
	
	[data-theme-mode=dark] .search-result-item:hover {
		background-color: rgba(255, 255, 255, 0.05);
	}
	
	/* RTL Header Fix */
	[dir="rtl"] .main-header-container {
		flex-direction: row-reverse;
	}
	
	[dir="rtl"] .header-content-left {
		order: 2;
	}
	
	[dir="rtl"] .header-content-right {
		order: 1;
	}
	
	/* Profile Dropdown Positioning Fix */
	.header-profile-dropdown {
		position: absolute !important;
		right: 0 !important;
		left: auto !important;
		z-index: 1050 !important;
	}
	
	[dir="rtl"] .header-profile-dropdown {
		right: auto !important;
		left: 0 !important;
	}
	
	/* Prevent sticky.js errors */
	.sticky-top {
		position: -webkit-sticky;
		position: sticky;
		top: 0;
		z-index: 1020;
	}
	
	/* Notifications Dropdown Alignment Fix */
	.header-element.notifications-dropdown {
		display: flex;
		align-items: center;
	}
	
	.header-element.notifications-dropdown .header-link {
		display: flex;
		align-items: center;
		justify-content: center;
	}
	
	/* Notifications Dropdown Positioning Fix */
	.notifications-dropdown .main-header-dropdown,
	.notifications-dropdown-menu {
		position: absolute !important;
		right: 0 !important;
		left: auto !important;
		z-index: 1050 !important;
		max-width: 400px;
		width: 100%;
		max-height: 500px;
		overflow-y: auto;
		margin-top: 0.5rem;
	}
	
	[dir="rtl"] .notifications-dropdown .main-header-dropdown,
	[dir="rtl"] .notifications-dropdown-menu {
		right: auto !important;
		left: 0 !important;
	}
	
	/* Sidebar and Content positioning - Let styles.css handle it completely */
	</style>
	
	<!-- Dark Mode Initialization (EXACT COPY FROM MANELI) - Must run before page renders -->
	<script>
	(function() {
		// Initialize theme immediately from localStorage to prevent flash
		try {
			if (typeof Storage !== "undefined") {
				var html = document.documentElement;
				if (html) {
					var isDark = localStorage.getItem("xintradarktheme") === "true";
					
					if (isDark) {
						html.setAttribute("data-theme-mode", "dark");
						if (localStorage.getItem("xintraHeader")) {
							html.setAttribute("data-header-styles", localStorage.getItem("xintraHeader"));
						} else {
							html.setAttribute("data-header-styles", "dark");
						}
						if (localStorage.getItem("xintraMenu")) {
							html.setAttribute("data-menu-styles", localStorage.getItem("xintraMenu"));
						} else {
							html.setAttribute("data-menu-styles", "dark");
						}
						
						// Set CSS variables for dark mode
						if (!localStorage.getItem("bodyBgRGB")) {
							html.style.setProperty("--body-bg-rgb", "25, 25, 28");
							html.style.setProperty("--body-bg-rgb2", "45, 45, 48");
							html.style.setProperty("--light-rgb", "43, 46, 49");
							html.style.setProperty("--form-control-bg", "rgb(25, 25, 28)");
							html.style.setProperty("--input-border", "rgba(255, 255, 255, 0.1)");
							html.style.setProperty("--default-body-bg-color", "rgb(45, 45, 48)");
							html.style.setProperty("--menu-bg", "rgb(25, 25, 28)");
							html.style.setProperty("--header-bg", "rgb(25, 25, 28)");
							html.style.setProperty("--custom-white", "rgb(25, 25, 28)");
						}
					} else {
						// Light mode - explicitly set
						html.setAttribute("data-theme-mode", "light");
						html.setAttribute("data-header-styles", "light");
						html.setAttribute("data-menu-styles", "dark");
						
						// Remove any dark mode CSS variables
						html.style.removeProperty("--body-bg-rgb");
						html.style.removeProperty("--body-bg-rgb2");
						html.style.removeProperty("--light-rgb");
						html.style.removeProperty("--form-control-bg");
						html.style.removeProperty("--input-border");
						html.style.removeProperty("--default-body-bg-color");
						html.style.removeProperty("--menu-bg");
						html.style.removeProperty("--header-bg");
						html.style.removeProperty("--custom-white");
					}
					
					// Initialize language and direction
					var savedLang = localStorage.getItem("pzl_language") || "<?php echo get_locale() === 'fa_IR' ? 'fa' : 'en'; ?>";
					if (savedLang === 'fa') {
						html.setAttribute('lang', 'fa');
						html.setAttribute('dir', 'rtl');
					} else {
						html.setAttribute('lang', 'en');
						html.setAttribute('dir', 'ltr');
					}
				}
			}
		} catch(e) {
			// Silently fail if localStorage is not available
		}
	})();
	</script>
	
	<?php
	/**
	 * Fires in the head section of dashboard
	 *
	 * Use this to enqueue scripts and styles
	 */
	do_action( 'puzzlingcrm_dashboard_head' );
	
	// Essential Functions for Header (EXACT COPY FROM MANELI - must be in head before body)
	?>
	<script>
	// Toggle Fullscreen (EXACT COPY FROM MANELI)
	function toggleFullscreen() {
		if (!document.fullscreenElement && !document.webkitFullscreenElement && !document.mozFullScreenElement) {
			if (document.documentElement.requestFullscreen) {
				document.documentElement.requestFullscreen();
			} else if (document.documentElement.webkitRequestFullscreen) {
				document.documentElement.webkitRequestFullscreen();
			} else if (document.documentElement.mozRequestFullScreen) {
				document.documentElement.mozRequestFullScreen();
			}
		} else {
			if (document.exitFullscreen) {
				document.exitFullscreen();
			} else if (document.webkitExitFullscreen) {
				document.webkitExitFullscreen();
			} else if (document.mozCancelFullScreen) {
				document.mozCancelFullScreen();
			}
		}
	}
	
	// Change Language Function (EXACT COPY FROM MANELI)
	(function() {
		const COOKIE_NAME = 'pzl_language';
		const COOKIE_MAX_AGE = 60 * 60 * 24 * 30; // 30 days
		const SERVER_LANGUAGE = <?php echo wp_json_encode( $locale === 'fa_IR' ? 'fa' : 'en' ); ?>;

		function normalizeLanguage(lang) {
			const serverNormalized = (SERVER_LANGUAGE === 'en') ? 'en' : 'fa';
			if (!lang) {
				return serverNormalized;
			}
			const value = String(lang).toLowerCase().trim();
			if (value === '') {
				return serverNormalized;
			}
			if (value.startsWith('en') || value === 'english') {
				return 'en';
			}
			if (value.startsWith('fa') || value === 'persian') {
				return 'fa';
			}
			return serverNormalized;
		}

		function setLanguageCookie(lang) {
			try {
				const normalized = normalizeLanguage(lang);
				document.cookie = COOKIE_NAME + '=' + normalized + '; path=/; max-age=' + COOKIE_MAX_AGE + '; SameSite=Lax';
			} catch (error) {
				console.warn('[PuzzlingCRM] Unable to persist language cookie', error);
			}
		}

		function applyLanguage(lang) {
			const normalized = normalizeLanguage(lang);
			const html = document.documentElement;
			const body = document.body;

			if (html) {
				html.lang = normalized === 'fa' ? 'fa' : 'en';
				html.dir = normalized === 'fa' ? 'rtl' : 'ltr';
			}

			if (body) {
				body.classList.toggle('rtl', normalized === 'fa');
				body.classList.toggle('ltr', normalized !== 'fa');
			}

			// Update Bootstrap CSS for RTL/LTR
			const styleLink = document.getElementById('style');
			if (styleLink) {
				const currentHref = styleLink.getAttribute('href') || '';
				const rtlHref = currentHref.includes('bootstrap.min.css') ? currentHref.replace('bootstrap.min.css', 'bootstrap.rtl.min.css') : currentHref;
				const ltrHref = currentHref.includes('bootstrap.rtl.min.css') ? currentHref.replace('bootstrap.rtl.min.css', 'bootstrap.min.css') : currentHref;
				styleLink.setAttribute('href', normalized === 'fa' ? rtlHref : ltrHref);
			}

			return normalized;
		}

		window.changeLanguage = function(lang) {
			const normalized = normalizeLanguage(lang);
			const currentLang = normalizeLanguage(document.documentElement.lang);
			const currentDir = (document.documentElement.dir || 'rtl').toLowerCase();

			localStorage.setItem('pzl_language', normalized);
			setLanguageCookie(normalized);

			// Apply immediately for visual feedback
			applyLanguage(normalized);

			const requiresReload = normalized !== currentLang ||
				(normalized === 'fa' && currentDir !== 'rtl') ||
				(normalized !== 'fa' && currentDir !== 'ltr');

			if (requiresReload) {
				localStorage.setItem('pzl_language_changing', 'true');
				window.location.reload();
			}
		};

		document.addEventListener('DOMContentLoaded', function() {
			const savedLang = normalizeLanguage(localStorage.getItem('pzl_language'));
			const appliedLang = applyLanguage(savedLang);
			setLanguageCookie(appliedLang);

			if (localStorage.getItem('pzl_language_changing') === 'true') {
				localStorage.removeItem('pzl_language_changing');
				return;
			}

			const currentLang = normalizeLanguage(document.documentElement.lang);
			const currentDir = (document.documentElement.dir || 'rtl').toLowerCase();

			const needsReload = appliedLang !== currentLang ||
				(appliedLang === 'fa' && currentDir !== 'rtl') ||
				(appliedLang !== 'fa' && currentDir !== 'ltr');

			if (needsReload) {
				localStorage.setItem('pzl_language_changing', 'true');
				window.location.reload();
			}
		});
	})();
	
	// Dark Mode Toggle (EXACT COPY FROM MANELI)
	(function() {
		console.log("PuzzlingCRM: Initializing dark mode toggle");
		
		function puzzlingToggleTheme() {
			var html = document.querySelector("html");
			if (!html) return;
			
			if (html.getAttribute("data-theme-mode") === "dark") {
				// Switch to light mode
				html.setAttribute("data-theme-mode", "light");
				html.setAttribute("data-header-styles", "light");
				html.setAttribute("data-menu-styles", "dark");
				if (!localStorage.getItem("primaryRGB")) {
					html.setAttribute("style", "");
				}
				html.style.removeProperty("--body-bg-rgb");
				html.style.removeProperty("--body-bg-rgb2");
				html.style.removeProperty("--light-rgb");
				html.style.removeProperty("--form-control-bg");
				html.style.removeProperty("--input-border");
				html.style.removeProperty("--default-body-bg-color");
				html.style.removeProperty("--menu-bg");
				html.style.removeProperty("--header-bg");
				html.style.removeProperty("--custom-white");
				localStorage.removeItem("xintradarktheme");
				localStorage.removeItem("xintraMenu");
				localStorage.removeItem("xintraHeader");
				localStorage.removeItem("bodylightRGB");
				localStorage.removeItem("bodyBgRGB");
				console.log("PuzzlingCRM: Switched to light mode");
			} else {
				// Switch to dark mode
				html.setAttribute("data-theme-mode", "dark");
				html.setAttribute("data-header-styles", "dark");
				html.setAttribute("data-menu-styles", "dark");
				if (!localStorage.getItem("primaryRGB")) {
					html.setAttribute("style", "");
				}
				
				// Set CSS variables for dark mode
				if (!localStorage.getItem("bodyBgRGB")) {
					html.style.setProperty("--body-bg-rgb", "25, 25, 28");
					html.style.setProperty("--body-bg-rgb2", "45, 45, 48");
					html.style.setProperty("--light-rgb", "43, 46, 49");
					html.style.setProperty("--form-control-bg", "rgb(25, 25, 28)");
					html.style.setProperty("--input-border", "rgba(255, 255, 255, 0.1)");
					html.style.setProperty("--default-body-bg-color", "rgb(45, 45, 48)");
					html.style.setProperty("--menu-bg", "rgb(25, 25, 28)");
					html.style.setProperty("--header-bg", "rgb(25, 25, 28)");
					html.style.setProperty("--custom-white", "rgb(25, 25, 28)");
				}
				
				localStorage.setItem("xintradarktheme", "true");
				localStorage.setItem("xintraMenu", "dark");
				localStorage.setItem("xintraHeader", "dark");
				localStorage.removeItem("bodylightRGB");
				localStorage.removeItem("bodyBgRGB");
				console.log("PuzzlingCRM: Switched to dark mode");
			}
		}
		
		function initPuzzlingDarkModeToggle() {
			var layoutSetting = document.querySelector(".layout-setting");
			if (layoutSetting) {
				console.log("PuzzlingCRM: Found .layout-setting element");
				
				// Clone to remove existing listeners
				var parent = layoutSetting.parentNode;
				if (parent) {
					var newLayoutSetting = layoutSetting.cloneNode(true);
					parent.replaceChild(newLayoutSetting, layoutSetting);
					
					newLayoutSetting.addEventListener("click", function(e) {
						e.preventDefault();
						e.stopPropagation();
						console.log("PuzzlingCRM: Dark mode toggle button clicked");
						puzzlingToggleTheme();
						return false;
					});
					
					console.log("PuzzlingCRM: Dark mode toggle initialized successfully");
				}
			} else {
				console.warn("PuzzlingCRM: .layout-setting element not found, will retry");
				setTimeout(initPuzzlingDarkModeToggle, 100);
			}
		}
		
		// Try multiple times to ensure it works
		if (document.readyState === "loading") {
			document.addEventListener("DOMContentLoaded", function() {
				setTimeout(initPuzzlingDarkModeToggle, 100);
			});
		} else {
			setTimeout(initPuzzlingDarkModeToggle, 100);
		}
		
		// Also try after window load
		window.addEventListener("load", function() {
			setTimeout(initPuzzlingDarkModeToggle, 200);
		});
	})();
	</script>
	
	<?php
	// WordPress head hook (loads jQuery and core scripts + all enqueued assets)
	wp_head();
	?>
	
	<!-- CRITICAL: Initialize All Header Functions (INLINE IN HEAD) -->
	<script>
	console.log('PuzzlingCRM: Initialization script started in HEAD at', new Date().getTime());
	
	// This will be defined in footer, but we check here
	window.puzzlingInitReady = false;
	</script>
</head>
<body class="app sidebar-mini <?php echo esc_attr( $direction ); ?> <?php echo esc_attr( 'role-' . $user_role ); ?>">
	
	<!-- CRITICAL: Initialize All Functions IMMEDIATELY (BEFORE ANYTHING ELSE) -->
	<script>
	(function() {
		console.log('PuzzlingCRM: BODY script started at', new Date().getTime());
		
		// Bootstrap initialization will happen after wp_footer() loads scripts
		
		// This will be defined in footer, but we start checking here
		window.puzzlingInitStarted = true;
	})();
	</script>
	
	<!-- Page Loader -->
	<div id="loader" style="display: none;">
		<img src="<?php echo esc_url( PUZZLINGCRM_PLUGIN_URL . 'assets/images/media/loader.svg' ); ?>" alt="<?php esc_attr_e( 'Loading...', 'puzzlingcrm' ); ?>">
	</div>
	<!-- End Page Loader -->
	
	<!-- Switcher Icon -->
	<div class="offcanvas offcanvas-end" tabindex="-1" id="switcher-canvas" aria-labelledby="offcanvasRightLabel">
		<div class="offcanvas-header border-bottom">
			<h5 class="offcanvas-title" id="offcanvasRightLabel"><?php esc_html_e( 'Switcher', 'puzzlingcrm' ); ?></h5>
			<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
		</div>
		<div class="offcanvas-body">
			<div class="theme-settings">
				<!-- Theme settings content would go here -->
			</div>
		</div>
	</div>
	<!-- End Switcher Icon -->
	
	<!-- Page -->
	<div class="page">
		
		<!-- Header -->
		<?php
		if ( $header ) {
			$header->render();
		}
		?>
		<!-- End Header -->
		
		<!-- Sidebar -->
		<?php
		if ( $sidebar ) {
			$sidebar->render();
		}
		?>
		<!-- End Sidebar -->
		
		<!-- Start::app-content -->
		<div class="main-content app-content">
			<div class="container-fluid">
				
				<?php
				/**
				 * Fires before dashboard content
				 */
				do_action( 'puzzlingcrm_before_dashboard_content' );
				
				// Load page content
				$template_path = PUZZLINGCRM_PLUGIN_DIR . 'templates/partials/';
				$partial_file  = '';
				
				// Determine which partial to load based on current page
				if ( empty( $current_page ) ) {
					// Dashboard home
					switch ( $user_role ) {
						case 'system_manager':
							$partial_file = $template_path . 'dashboard-system-manager.php';
							break;
						case 'finance_manager':
							$partial_file = $template_path . 'dashboard-finance.php';
							break;
						case 'team_member':
							$partial_file = $template_path . 'dashboard-team-member.php';
							break;
						case 'customer':
							$partial_file = $template_path . 'dashboard-client.php';
							break;
					}
				} else {
					// Other pages
					$routes = PuzzlingCRM_Dashboard_Router::get_routes();
					if ( isset( $routes[ $current_page ] ) && ! empty( $routes[ $current_page ]['partial'] ) ) {
						$partial_file = $template_path . $routes[ $current_page ]['partial'] . '.php';
					}
				}
				
				// Load the partial
				if ( ! empty( $partial_file ) && file_exists( $partial_file ) ) {
					include $partial_file;
				} else {
					?>
					<div class="alert alert-danger">
						<?php esc_html_e( 'Page not found.', 'puzzlingcrm' ); ?>
					</div>
					<?php
				}
				
				/**
				 * Fires after dashboard content
				 */
				do_action( 'puzzlingcrm_after_dashboard_content' );
				?>
				
			</div>
		</div>
		<!-- End::app-content -->
		
	</div>
	<!-- End Page -->
	
	<!-- Footer (outside page div) -->
	<?php
	if ( $footer ) {
		$footer->render();
	}
	?>
	<!-- End Footer -->
	
	<?php
	// WordPress footer hook (loads all enqueued scripts)
	wp_footer();
	?>
	
	<?php
	/**
	 * Fires after wp_footer (for custom scripts)
	 */
	do_action( 'puzzlingcrm_dashboard_footer' );
	?>
	
	<!-- CRITICAL: Initialize Bootstrap Dropdowns (AFTER wp_footer) -->
	<script>
	(function() {
		console.log('PuzzlingCRM: Footer initialization started');
		
		var attempts = 0;
		var maxAttempts = 150;
		var initialized = false;
		
		function initBootstrapDropdowns() {
			if (initialized) {
				return;
			}
			
			attempts++;
			
			if (typeof bootstrap === 'undefined' || !bootstrap.Dropdown) {
				if (attempts < maxAttempts) {
					setTimeout(initBootstrapDropdowns, 200);
					return;
				}
				console.error('PuzzlingCRM: Bootstrap not found after', attempts, 'attempts');
				return;
			}
			
			console.log('PuzzlingCRM: Bootstrap found! Initializing dropdowns...');
			
			var dropdowns = document.querySelectorAll('[data-bs-toggle="dropdown"]');
			console.log('PuzzlingCRM: Found', dropdowns.length, 'dropdowns');
			
			var dropdownCount = 0;
			dropdowns.forEach(function(el, index) {
				try {
					if (!bootstrap.Dropdown.getInstance(el)) {
						new bootstrap.Dropdown(el);
						dropdownCount++;
						console.log('PuzzlingCRM: Dropdown', index + 1, 'initialized');
					}
				} catch (e) {
					console.error('PuzzlingCRM: Dropdown', index + 1, 'error:', e);
				}
			});
			
			console.log('PuzzlingCRM: Initialized', dropdownCount, 'new dropdowns');
			console.log('PuzzlingCRM: Bootstrap initialization completed');
			initialized = true;
		}
		
		// Try immediately
		setTimeout(initBootstrapDropdowns, 1000);
		
		// Also try after DOM ready
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', function() {
				setTimeout(initBootstrapDropdowns, 1500);
			});
		} else {
			setTimeout(initBootstrapDropdowns, 1500);
		}
		
		// Also try after window load
		window.addEventListener('load', function() {
			console.log('PuzzlingCRM: Window load event fired');
			attempts = 0;
			initialized = false;
			setTimeout(initBootstrapDropdowns, 2000);
		});
	})();
	</script>
	
	<!-- Scroll To Top -->
	<div class="scrollToTop">
		<span class="arrow"><i class="ri-arrow-up-s-fill fs-20"></i></span>
	</div>
	<div id="responsive-overlay"></div>
	<!-- End Scroll To Top -->
	
	</body>
</html>
