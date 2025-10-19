<?php
/**
 * PuzzlingCRM Dashboard Router (Exact Copy of Xintra Template)
 * Manages all dashboard routes with clean URLs
 *
 * @package PuzzlingCRM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class PuzzlingCRM_Dashboard_Router {

    /**
     * All available dashboard routes
     */
    private static $routes = [
        // Main dashboard
        '' => [
            'title' => 'داشبورد',
            'icon' => 'ri-home-4-line',
            'roles' => ['system_manager', 'team_member', 'client'],
            'partial' => 'dashboard',
        ],
        
        // Projects
        'projects' => [
            'title' => 'پروژه‌ها',
            'icon' => 'ri-folder-2-line',
            'roles' => ['system_manager', 'team_member', 'client'],
            'partial' => 'page-projects',
        ],
        
        // Contracts
        'contracts' => [
            'title' => 'قراردادها',
            'icon' => 'ri-file-text-line',
            'roles' => ['system_manager', 'team_member', 'client'],
            'partial' => 'page-contracts',
        ],
        
        // Invoices
        'invoices' => [
            'title' => 'پیش‌فاکتورها',
            'icon' => 'ri-file-list-3-line',
            'roles' => ['system_manager', 'team_member', 'client'],
            'partial' => 'page-pro-invoices',
        ],
        
        // Tickets
        'tickets' => [
            'title' => 'تیکت‌ها',
            'icon' => 'ri-customer-service-2-line',
            'roles' => ['system_manager', 'team_member', 'client'],
            'partial' => 'page-tickets',
        ],
        
        // Tasks
        'tasks' => [
            'title' => 'وظایف',
            'icon' => 'ri-task-line',
            'roles' => ['system_manager', 'team_member'],
            'partial' => 'page-tasks',
        ],
        
        // Appointments
        'appointments' => [
            'title' => 'قرار ملاقات‌ها',
            'icon' => 'ri-calendar-check-line',
            'roles' => ['system_manager', 'team_member', 'client'],
            'partial' => 'page-appointments',
        ],
        
        // Leads (Manager only)
        'leads' => [
            'title' => 'سرنخ‌ها',
            'icon' => 'ri-user-add-line',
            'roles' => ['system_manager'],
            'partial' => 'page-leads',
        ],
        
        // Customers (Manager only)
        'customers' => [
            'title' => 'مشتریان',
            'icon' => 'ri-group-line',
            'roles' => ['system_manager'],
            'partial' => 'page-customers',
        ],
        
        // Staff (Manager only)
        'staff' => [
            'title' => 'کارکنان',
            'icon' => 'ri-user-star-line',
            'roles' => ['system_manager'],
            'partial' => 'page-staff',
        ],
        
        // Consultations (Manager only)
        'consultations' => [
            'title' => 'مشاوره‌ها',
            'icon' => 'ri-discuss-line',
            'roles' => ['system_manager'],
            'partial' => 'page-consultations',
        ],
        
        // Reports (Manager only)
        'reports' => [
            'title' => 'گزارشات',
            'icon' => 'ri-bar-chart-box-line',
            'roles' => ['system_manager'],
            'partial' => 'page-reports',
        ],
        
        // Logs (Manager only)
        'logs' => [
            'title' => 'لاگ‌ها',
            'icon' => 'ri-file-list-2-line',
            'roles' => ['system_manager'],
            'partial' => 'page-logs',
        ],
        
        // Settings (Manager only)
        'settings' => [
            'title' => 'تنظیمات',
            'icon' => 'ri-settings-3-line',
            'roles' => ['system_manager'],
            'partial' => 'page-settings',
        ],
        
        // My Profile (All logged-in users)
        'profile' => [
            'title' => 'پروفایل من',
            'icon' => 'ri-user-3-line',
            'roles' => ['system_manager', 'team_member', 'client'],
            'partial' => 'page-my-profile',
        ],
    ];

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'template_redirect']);
    }

    public function add_rewrite_rules() {
        add_rewrite_rule('^dashboard/?$', 'index.php?puzzling_dashboard=1', 'top');
        add_rewrite_rule('^dashboard/([^/]+)/?$', 'index.php?puzzling_dashboard=1&dashboard_page=$matches[1]', 'top');
        add_rewrite_rule('^dashboard/([^/]+)/([0-9]+)/?$', 'index.php?puzzling_dashboard=1&dashboard_page=$matches[1]&item_id=$matches[2]', 'top');
    }

    public function add_query_vars($vars) {
        $vars[] = 'puzzling_dashboard';
        $vars[] = 'dashboard_page';
        $vars[] = 'item_id';
        return $vars;
    }

    public function template_redirect() {
        if (get_query_var('puzzling_dashboard')) {
            $this->load_dashboard_template();
            exit;
        }
    }

    private function load_dashboard_template() {
        if (!is_user_logged_in()) {
            wp_redirect(home_url('/login'));
            exit;
        }

        $page = get_query_var('dashboard_page');
        if (empty($page)) {
            $page = '';
        }

        if (!isset(self::$routes[$page])) {
            wp_redirect(home_url('/dashboard'));
            exit;
        }

        $user = wp_get_current_user();
        $user_role = $this->get_user_dashboard_role($user);
        $route = self::$routes[$page];

        if (!in_array($user_role, $route['roles'])) {
            wp_redirect(home_url('/dashboard'));
            exit;
        }

        $this->render_dashboard_wrapper($page, $route, $user);
    }

    private function render_dashboard_wrapper($current_page, $route, $user) {
        $assets_url = PUZZLINGCRM_PLUGIN_URL . 'assets/';
        $custom_logo_id = get_theme_mod('custom_logo');
        $logo_url = '';
        if ($custom_logo_id) {
            $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
            $logo_url = $logo[0];
        }
        ?>
<!DOCTYPE html>
<html lang="fa" dir="rtl" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light" data-menu-styles="dark" data-toggled="close">

    <head>

        <!-- Meta Data -->
        <meta charset="UTF-8">
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="Description" content="<?php bloginfo('description'); ?>">
        <meta name="Author" content="<?php bloginfo('name'); ?>">
        
		<!-- Title -->
        <title><?php echo esc_html($route['title']); ?> - <?php bloginfo('name'); ?></title>

        <!-- Favicon -->
        <link rel="icon" href="<?php echo $assets_url; ?>images/brand-logos/favicon.ico" type="image/x-icon">

        <!-- Start::Styles -->
        
        <!-- Choices JS -->
        <script src="<?php echo $assets_url; ?>libs/choices.js/public/assets/scripts/choices.min.js"></script>

        <!-- Main Theme Js -->
        <script src="<?php echo $assets_url; ?>js/main.js"></script>
        
        <!-- Bootstrap Css -->
        <link id="style" href="<?php echo $assets_url; ?>libs/bootstrap/css/bootstrap.rtl.min.css" rel="stylesheet">

        <!-- Fonts (قبل از همه) -->
        <link href="<?php echo $assets_url; ?>css/fonts.css" rel="stylesheet">

        <!-- Style Css -->
        <link href="<?php echo $assets_url; ?>css/styles.css" rel="stylesheet">

        <!-- Icons Css -->
        <link href="<?php echo $assets_url; ?>css/icons.css" rel="stylesheet">

        <!-- Node Waves Css -->
        <link href="<?php echo $assets_url; ?>libs/node-waves/waves.min.css" rel="stylesheet"> 

        <!-- Simplebar Css -->
        <link href="<?php echo $assets_url; ?>libs/simplebar/simplebar.min.css" rel="stylesheet">
        
        <!-- Choices Css -->
        <link rel="stylesheet" href="<?php echo $assets_url; ?>libs/choices.js/public/assets/styles/choices.min.css">

        <!-- PuzzlingCRM Bridge Styles (Old classes to Xintra) -->
        <link href="<?php echo $assets_url; ?>css/puzzlingcrm-xintra-bridge.css" rel="stylesheet">
        
        <!-- PuzzlingCRM Custom Styles -->
        <link href="<?php echo $assets_url; ?>css/puzzlingcrm-custom.css" rel="stylesheet">
        
        <!-- RTL Complete Fix (آخرین فایل - بالاترین اولویت) -->
        <link href="<?php echo $assets_url; ?>css/rtl-complete-fix.css" rel="stylesheet">
        
        <?php
        // Load page-specific styles
        $view = isset($_GET['view']) ? sanitize_key($_GET['view']) : 'dashboard';
        if ($view === 'reports') {
            echo '<link rel="stylesheet" href="' . esc_url(PUZZLINGCRM_PLUGIN_URL . 'assets/css/reports-styles.css') . '?v=' . PUZZLINGCRM_VERSION . '">';
        }
        if ($view === 'dashboard') {
            echo '<link rel="stylesheet" href="' . esc_url(PUZZLINGCRM_PLUGIN_URL . 'assets/css/dashboard-styles.css') . '?v=' . PUZZLINGCRM_VERSION . '">';
        }
        // Load complete styles for all other pages
        if (!in_array($view, ['reports', 'dashboard'])) {
            echo '<link rel="stylesheet" href="' . esc_url(PUZZLINGCRM_PLUGIN_URL . 'assets/css/all-pages-complete.css') . '?v=' . PUZZLINGCRM_VERSION . '">';
        }
        ?>
        
        <!-- jQuery (for AJAX) -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        
        <!-- jQuery UI (for Sortable/Draggable) -->
        <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
        <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.min.css">
        
        <!-- SweetAlert2 for AJAX notifications -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        
        <!-- Persian Date Library -->
        <script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js"></script>
        
        <!-- FullCalendar -->
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
        
        <!-- DHTMLX Gantt -->
        <script src="https://cdn.dhtmlx.com/gantt/edge/dhtmlxgantt.js"></script>
        <link rel="stylesheet" href="https://cdn.dhtmlx.com/gantt/edge/dhtmlxgantt.css">
        
        <!-- Chart.js for Advanced Charts -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        
        <!-- jsPDF for PDF Export -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        
        <!-- SheetJS for Excel Export -->
        <script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
        
        <!-- PuzzlingCRM AJAX Config -->
        <script>
        var puzzlingcrm_ajax_obj = {
            ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('puzzlingcrm-ajax-nonce'); ?>',
            lang: {
                success_title: 'موفق',
                error_title: 'خطا',
                ok_button: 'باشه',
                server_error: 'خطای سرور'
            }
        };
        </script>
        <!-- End::Styles -->

    </head>

    <body class="">

        <!-- Loader -->
        <div id="loader">
            <img src="<?php echo $assets_url; ?>images/media/loader.svg" alt="">
        </div>
        <!-- Loader -->

        <div class="page">

            <!-- Start::main-header -->
            
			<header class="app-header sticky" id="header">

				<!-- Start::main-header-container -->
				<div class="main-header-container container-fluid">

					<!-- Start::header-content-left -->
					<div class="header-content-left">

						<!-- Start::header-element -->
						<div class="header-element">
							<div class="horizontal-logo">
								<a href="<?php echo esc_url(home_url('/dashboard')); ?>" class="header-logo">
									<?php if ($logo_url): ?>
                                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="desktop-logo" style="max-height: 45px;">
                                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="toggle-dark" style="max-height: 45px;">
                                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="desktop-dark" style="max-height: 45px;">
                                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="toggle-logo" style="max-height: 45px;">
                                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="toggle-white" style="max-height: 45px;">
                                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="desktop-white" style="max-height: 45px;">
									<?php else: ?>
                                        <img src="<?php echo $assets_url; ?>images/brand-logos/desktop-logo.png" alt="logo" class="desktop-logo">
										<img src="<?php echo $assets_url; ?>images/brand-logos/toggle-dark.png" alt="logo" class="toggle-dark">
										<img src="<?php echo $assets_url; ?>images/brand-logos/desktop-dark.png" alt="logo" class="desktop-dark">
										<img src="<?php echo $assets_url; ?>images/brand-logos/toggle-logo.png" alt="logo" class="toggle-logo">
										<img src="<?php echo $assets_url; ?>images/brand-logos/toggle-white.png" alt="logo" class="toggle-white">
										<img src="<?php echo $assets_url; ?>images/brand-logos/desktop-white.png" alt="logo" class="desktop-white">
									<?php endif; ?>
								</a>
							</div>
						</div>
						<!-- End::header-element -->

						<!-- Start::header-element -->
						<div class="header-element mx-lg-0 mx-2">
							<a aria-label="Hide Sidebar" class="sidemenu-toggle header-link animated-arrow hor-toggle horizontal-navtoggle" data-bs-toggle="sidebar" href="javascript:void(0);"><span></span></a>
						</div>
						<!-- End::header-element -->

					</div>
					<!-- End::header-content-left -->

					<!-- Start::header-content-right -->
					<ul class="header-content-right">

						<!-- Start::header-element -->
						<li class="header-element header-theme-mode">
							<!-- Start::header-link|layout-setting -->
							<a href="javascript:void(0);" class="header-link layout-setting">
								<span class="light-layout">
									<!-- Start::header-link-icon -->
									<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 header-link-icon" fill="none" viewbox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"></path>
									</svg>
									<!-- End::header-link-icon -->
								</span>
								<span class="dark-layout">
									<!-- Start::header-link-icon -->
									<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 header-link-icon" fill="none" viewbox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"></path>
									</svg>
									<!-- End::header-link-icon -->
								</span>
							</a>
							<!-- End::header-link|layout-setting -->
						</li>
						<!-- End::header-element -->

						<!-- Start::header-element -->
						<li class="header-element dropdown">
							<!-- Start::header-link|dropdown-toggle -->
							<a href="javascript:void(0);" class="header-link dropdown-toggle" id="mainHeaderProfile" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
								<div class="d-flex align-items-center">
									<div class="me-2">
										<?php echo get_avatar($user->ID, 32, '', '', ['class' => 'rounded-circle']); ?>
									</div>
									<div class="d-xl-block d-none lh-1">
										<span class="fw-medium lh-1"><?php echo esc_html($user->display_name); ?></span>
									</div>
								</div>
							</a>
							<!-- End::header-link|dropdown-toggle -->
							<ul class="main-header-dropdown dropdown-menu pt-0 header-profile-dropdown dropdown-menu-end" aria-labelledby="mainHeaderProfile">
								<li>
									<div class="header-navheading border-bottom">
										<h6 class="main-notification-title"><?php echo esc_html($user->display_name); ?></h6>
										<p class="main-notification-text mb-0 fs-11"><?php echo esc_html($this->get_role_label($user)); ?></p>
									</div>
								</li>
								<li><a class="dropdown-item d-flex align-items-center" href="<?php echo esc_url(home_url('/dashboard/profile')); ?>"><i class="ri-user-3-line fs-16 align-middle me-2"></i>پروفایل من</a></li>
								<li><a class="dropdown-item d-flex align-items-center" href="<?php echo esc_url(wp_logout_url(home_url('/login'))); ?>"><i class="ri-logout-box-line fs-16 align-middle me-2"></i>خروج</a></li>
							</ul>
						</li>
						<!-- End::header-element -->

					</ul>
					<!-- End::header-content-right -->

				</div>
				<!-- End::main-header-container -->

			</header>
            
            <!-- End::main-header -->

            <!-- Start::main-sidebar -->
            
			<aside class="app-sidebar sticky" id="sidebar">

				<!-- Start::main-sidebar-header -->
				<div class="main-sidebar-header">
					<a href="<?php echo esc_url(home_url('/dashboard')); ?>" class="header-logo">
						<?php if ($logo_url): ?>
                            <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="desktop-logo" style="max-height: 45px;">
							<img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="toggle-dark" style="max-height: 45px;">
							<img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="desktop-dark" style="max-height: 45px;">
							<img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="toggle-logo" style="max-height: 45px;">
							<img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="toggle-white" style="max-height: 45px;">
							<img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="desktop-white" style="max-height: 45px;">
						<?php else: ?>
                            <img src="<?php echo $assets_url; ?>images/brand-logos/desktop-logo.png" alt="logo" class="desktop-logo">
							<img src="<?php echo $assets_url; ?>images/brand-logos/toggle-dark.png" alt="logo" class="toggle-dark">
							<img src="<?php echo $assets_url; ?>images/brand-logos/desktop-dark.png" alt="logo" class="desktop-dark">
							<img src="<?php echo $assets_url; ?>images/brand-logos/toggle-logo.png" alt="logo" class="toggle-logo">
							<img src="<?php echo $assets_url; ?>images/brand-logos/toggle-white.png" alt="logo" class="toggle-white">
							<img src="<?php echo $assets_url; ?>images/brand-logos/desktop-white.png" alt="logo" class="desktop-white">
						<?php endif; ?>
					</a>
				</div>
				<!-- End::main-sidebar-header -->

				<!-- Start::main-sidebar -->
				<div class="main-sidebar" id="sidebar-scroll">

					<!-- Start::nav -->
					<nav class="main-menu-container nav nav-pills flex-column sub-open">
						<div class="slide-left" id="slide-left">
							<svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewbox="0 0 24 24"> <path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z"></path> </svg>
						</div>
						<ul class="main-menu">
							<!-- Start::slide__category -->
							<li class="slide__category"><span class="category-name">منوی اصلی</span></li>
							<!-- End::slide__category -->

							<?php echo $this->render_navigation_menu($current_page, $user); ?>

						</ul>
						<div class="slide-right" id="slide-right"><svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewbox="0 0 24 24"> <path d="M10.707 17.707 16.414 12l-5.707-5.707-1.414 1.414L13.586 12l-4.293 4.293z"></path> </svg></div>
					</nav>
					<!-- End::nav -->

				</div>
				<!-- End::main-sidebar -->

			</aside>
            <!-- End::main-sidebar -->

            <!-- Start::app-content -->
            <div class="main-content app-content">
                <div class="container-fluid">

                    	
                    <!-- Start::page-header -->
                    <div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2">
                        <div>
                            <nav>
                                <ol class="breadcrumb mb-1">
                                    <li class="breadcrumb-item">
                                        <a href="<?php echo esc_url(home_url('/dashboard')); ?>">
                                            داشبورد
                                        </a>
                                    </li>
                                    <?php if ($current_page): ?>
                                    <li class="breadcrumb-item active" aria-current="page"><?php echo esc_html($route['title']); ?></li>
                                    <?php endif; ?>
                                </ol>
                            </nav>
                            <h1 class="page-title fw-medium fs-18 mb-0"><?php echo esc_html($route['title']); ?></h1>
                        </div>
                    </div>
                    <!-- End::page-header -->

                    <!-- Start::row-1 -->
                    <?php $this->render_page_content($current_page, $route, $user); ?>
                    <!-- End::row-1 -->

                </div>
            </div>
            <!-- End::app-content -->


        </div>


        <!-- Start::custom-scripts -->
        
        <!-- Bootstrap JS -->
        <script src="<?php echo $assets_url; ?>libs/bootstrap/js/bootstrap.bundle.min.js"></script>

        <!-- Simplebar JS -->
        <script src="<?php echo $assets_url; ?>libs/simplebar/simplebar.min.js"></script>

        <!-- Color Picker JS -->
        <script src="<?php echo $assets_url; ?>libs/%40simonwep/pickr/pickr.es5.min.js"></script>

        <!-- Sticky JS -->
        <script src="<?php echo $assets_url; ?>js/sticky.js"></script>

        <!-- Custom-Switcher JS -->
        <script src="<?php echo $assets_url; ?>js/custom-switcher.min.js"></script>

        <!-- Custom JS -->
        <script src="<?php echo $assets_url; ?>js/custom.js"></script>
        
        <!-- PuzzlingCRM Scripts -->
        <script src="<?php echo PUZZLINGCRM_PLUGIN_URL; ?>assets/js/puzzlingcrm-scripts.js"></script>
        <script src="<?php echo PUZZLINGCRM_PLUGIN_URL; ?>assets/js/tasks-management.js"></script>
        <script src="<?php echo PUZZLINGCRM_PLUGIN_URL; ?>assets/js/user-management.js"></script>
        <script src="<?php echo PUZZLINGCRM_PLUGIN_URL; ?>assets/js/lead-management.js"></script>
        <script src="<?php echo PUZZLINGCRM_PLUGIN_URL; ?>assets/js/forms-enhancement.js?v=<?php echo PUZZLINGCRM_VERSION; ?>"></script>
        <script src="<?php echo PUZZLINGCRM_PLUGIN_URL; ?>assets/js/pdf-generator.js?v=<?php echo PUZZLINGCRM_VERSION; ?>"></script>
        <script src="<?php echo PUZZLINGCRM_PLUGIN_URL; ?>assets/js/bulk-actions.js?v=<?php echo PUZZLINGCRM_VERSION; ?>"></script>
        <script src="<?php echo PUZZLINGCRM_PLUGIN_URL; ?>assets/js/modals-handler.js?v=<?php echo PUZZLINGCRM_VERSION; ?>"></script>
        <script src="<?php echo PUZZLINGCRM_PLUGIN_URL; ?>assets/js/email-sender.js?v=<?php echo PUZZLINGCRM_VERSION; ?>"></script>
        <script src="<?php echo PUZZLINGCRM_PLUGIN_URL; ?>assets/js/import-export.js?v=<?php echo PUZZLINGCRM_VERSION; ?>"></script>
        
        <?php
        // Load reports export script for reports page
        $view = isset($_GET['view']) ? sanitize_key($_GET['view']) : 'dashboard';
        if ($view === 'reports') {
            echo '<script src="' . esc_url(PUZZLINGCRM_PLUGIN_URL . 'assets/js/reports-export.js') . '?v=' . PUZZLINGCRM_VERSION . '"></script>';
        }
        ?>
        <!-- End::custom-scripts -->

    </body>

</html>
        <?php
    }

    private function render_navigation_menu($current_page, $user) {
        $user_role = $this->get_user_dashboard_role($user);
        $output = '';
        
        foreach (self::$routes as $slug => $route) {
            if (!in_array($user_role, $route['roles'])) {
                continue;
            }

            $url = home_url('/dashboard' . ($slug ? '/' . $slug : ''));
            $active_class = ($current_page === $slug) ? ' active' : '';
            
            $output .= sprintf(
                '<li class="slide%s">
                    <a href="%s" class="side-menu__item">
                        <i class="%s side-menu__icon"></i>
                        <span class="side-menu__label">%s</span>
                    </a>
                </li>',
                $active_class,
                esc_url($url),
                esc_attr($route['icon']),
                esc_html($route['title'])
            );
        }
        
        return $output;
    }

    private function render_page_content($page, $route, $user) {
        $user_role = $this->get_user_dashboard_role($user);
        $partial = $this->get_partial_for_role($route['partial'], $user_role);
        $template_path = PUZZLING_CRM_TEMPLATE_PATH . 'partials/' . $partial . '.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="alert alert-danger">خطا: فایل قالب یافت نشد: ' . esc_html($partial) . '</div>';
        }
    }

    private function get_partial_for_role($base_partial, $user_role) {
        if ($base_partial === 'dashboard') {
            switch ($user_role) {
                case 'system_manager':
                    return 'dashboard-system-manager';
                case 'team_member':
                    return 'dashboard-team-member';
                case 'client':
                    return 'dashboard-client';
            }
        }
        
        if ($base_partial === 'page-projects') {
            switch ($user_role) {
                case 'system_manager':
                    return 'page-projects';
                case 'team_member':
                    return 'list-team-member-projects';
                case 'client':
                    return 'list-projects';
            }
        }
        
        if ($base_partial === 'page-appointments') {
            if ($user_role === 'client') {
                return 'page-client-appointments';
            }
            return 'page-appointments';
        }
        
        if ($base_partial === 'page-contracts') {
            if ($user_role === 'client') {
                return 'page-client-contracts';
            }
            return 'page-contracts';
        }
        
        if ($base_partial === 'page-pro-invoices') {
            if ($user_role === 'client') {
                return 'page-client-pro-invoices';
            }
            return 'page-pro-invoices';
        }
        
        return $base_partial;
    }

    private function get_user_dashboard_role($user) {
        $roles = (array) $user->roles;
        
        if (in_array('administrator', $roles) || in_array('system_manager', $roles)) {
            return 'system_manager';
        }
        if (in_array('team_member', $roles)) {
            return 'team_member';
        }
        if (in_array('client', $roles) || in_array('customer', $roles)) {
            return 'client';
        }
        
        return 'guest';
    }

    private function get_role_label($user) {
        $roles = (array) $user->roles;
        
        if (in_array('administrator', $roles)) {
            return 'مدیر کل';
        }
        if (in_array('system_manager', $roles)) {
            return 'مدیر سیستم';
        }
        if (in_array('team_member', $roles)) {
            return 'کارمند';
        }
        if (in_array('client', $roles) || in_array('customer', $roles)) {
            return 'مشتری';
        }
        
        return 'کاربر';
    }

    public static function get_user_routes() {
        if (!is_user_logged_in()) {
            return [];
        }

        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        
        $user_role = 'guest';
        if (in_array('administrator', $roles) || in_array('system_manager', $roles)) {
            $user_role = 'system_manager';
        } elseif (in_array('team_member', $roles)) {
            $user_role = 'team_member';
        } elseif (in_array('client', $roles) || in_array('customer', $roles)) {
            $user_role = 'client';
        }

        $available_routes = [];
        foreach (self::$routes as $slug => $route) {
            if (in_array($user_role, $route['roles'])) {
                $available_routes[$slug] = $route;
            }
        }

        return $available_routes;
    }

    public static function activate() {
        $instance = new self();
        $instance->add_rewrite_rules();
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }
}
